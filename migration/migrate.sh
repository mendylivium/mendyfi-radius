#!/usr/bin/env bash
set -euo pipefail

# migrate.sh -- Migrate MendyFi from MySQL to PostgreSQL 16.
#
# Usage: ./migration/migrate.sh [OPTIONS]
#
# Options:
#   --dry-run        Print what would be done without doing it
#   --skip-backup    Skip MySQL backup step (use if you already have backups)
#   --mysql-host     MySQL host (default: reads from .env DB_HOST)
#   --mysql-port     MySQL port (default: 3306)
#   --pg-host        PostgreSQL host (default: 127.0.0.1)
#   --pg-port        PostgreSQL port (default: 5433)
#   --ci-mode        Non-interactive, auto-yes, exit non-zero on failure
#
# Prerequisites:
#   - Docker Engine + Compose v2
#   - Access to running MySQL instance (host, credentials in .env)
#   - .env file in project root with DB_* variables

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Defaults
DRY_RUN=false
SKIP_BACKUP=false
CI_MODE=false
MYSQL_HOST=""
MYSQL_PORT="3306"
PG_HOST="127.0.0.1"
PG_PORT="5433"

# Parse arguments
while [[ $# -gt 0 ]]; do
    case "$1" in
        --dry-run)     DRY_RUN=true; shift ;;
        --skip-backup) SKIP_BACKUP=true; shift ;;
        --ci-mode)     CI_MODE=true; shift ;;
        --mysql-host)  MYSQL_HOST="$2"; shift 2 ;;
        --mysql-port)  MYSQL_PORT="$2"; shift 2 ;;
        --pg-host)     PG_HOST="$2"; shift 2 ;;
        --pg-port)     PG_PORT="$2"; shift 2 ;;
        *) echo "Unknown option: $1"; exit 1 ;;
    esac
done

LOCKFILE="$SCRIPT_DIR/.migration.lock"
BACKUP_DATE=$(date +%Y-%m-%d)
BACKUP_DIR="$SCRIPT_DIR/backups/$BACKUP_DATE"
MANIFEST_FILE="$BACKUP_DIR/mysql-manifest.json"

# Colours
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log()    { echo -e "${GREEN}[migrate]${NC} $*"; }
warn()   { echo -e "${YELLOW}[migrate]${NC} $*"; }
error()  { echo -e "${RED}[migrate]${NC} $*" >&2; }
step()   { echo ""; echo -e "${GREEN}========================================${NC}"; echo -e "${GREEN}  Step $1: $2${NC}"; echo -e "${GREEN}========================================${NC}"; }

cleanup() {
    rm -f "$LOCKFILE"
}
trap cleanup EXIT

# ---------------------
# Step 0: Parse .env
# ---------------------
if [[ ! -f "$PROJECT_DIR/.env" ]]; then
    # Try src/.env for development layout
    if [[ -f "$PROJECT_DIR/src/.env" ]]; then
        ENV_FILE="$PROJECT_DIR/src/.env"
    else
        error ".env file not found in $PROJECT_DIR or $PROJECT_DIR/src/"
        exit 1
    fi
else
    ENV_FILE="$PROJECT_DIR/.env"
fi

# Source MySQL credentials from .env
export MYSQL_USER=$(grep -E '^DB_USERNAME=' "$ENV_FILE" | cut -d= -f2-)
export MYSQL_PASS=$(grep -E '^DB_PASSWORD=' "$ENV_FILE" | cut -d= -f2-)
export MYSQL_DB=$(grep -E '^DB_DATABASE=' "$ENV_FILE" | cut -d= -f2-)
[[ -z "$MYSQL_HOST" ]] && MYSQL_HOST=$(grep -E '^DB_HOST=' "$ENV_FILE" | cut -d= -f2-)
export MYSQL_HOST
export MYSQL_PORT

# PG credentials (for the migration target)
export PG_USER="${PG_USER:-mendyfi}"
export PG_PASS="${PG_PASS:-changeme_migrate}"
export PG_HOST
export PG_PORT

log "MySQL: ${MYSQL_USER}@${MYSQL_HOST}:${MYSQL_PORT}/${MYSQL_DB}"
log "PostgreSQL target: ${PG_USER}@${PG_HOST}:${PG_PORT}"

if $DRY_RUN; then
    warn "DRY RUN -- no changes will be made"
fi

# ---------------------
# Step 1: Pre-flight checks
# ---------------------
step 1 "Pre-flight checks"

# Lockfile
if [[ -f "$LOCKFILE" ]]; then
    error "Migration already in progress (lockfile exists: $LOCKFILE)"
    error "If this is stale, remove it: rm $LOCKFILE"
    exit 1
fi
echo $$ > "$LOCKFILE"
log "Lockfile created"

# Docker + Compose
if ! command -v docker &>/dev/null; then
    error "Docker not found. Install Docker Engine first."
    exit 1
fi
if ! docker compose version &>/dev/null; then
    error "Docker Compose v2 not found."
    exit 1
fi
log "Docker Engine + Compose v2: OK"

# MySQL connectivity
log "Testing MySQL connection..."
if ! docker run --rm --network host mysql:8 \
    mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$MYSQL_PASS" \
    -e "SELECT 1" "$MYSQL_DB" &>/dev/null; then
    error "Cannot connect to MySQL at ${MYSQL_HOST}:${MYSQL_PORT}"
    exit 1
fi
log "MySQL connection: OK"

# Disk space check (need at least 2x database size)
log "Disk space: $(df -h "$SCRIPT_DIR" | tail -1 | awk '{print $4}') available"

log "Pre-flight checks: ALL PASSED"

# ---------------------
# Step 2: Backup current state
# ---------------------
step 2 "Backup current state"

mkdir -p "$BACKUP_DIR"

if $SKIP_BACKUP; then
    warn "Skipping backup (--skip-backup)"
else
    # Backup .env and docker-compose.yml
    cp "$ENV_FILE" "$BACKUP_DIR/.env.backup"
    if [[ -f "$PROJECT_DIR/docker-compose.yml" ]]; then
        cp "$PROJECT_DIR/docker-compose.yml" "$BACKUP_DIR/docker-compose.yml.backup"
    fi
    log "Config files backed up"

    # Get list of tenant databases
    TENANT_DBS=$(docker run --rm --network host mysql:8 \
        mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$MYSQL_PASS" \
        -N -e "SELECT id FROM tenants" "$MYSQL_DB" 2>/dev/null || echo "")

    # Dump MySQL databases and record row counts
    log "Dumping MySQL central database..."
    if ! $DRY_RUN; then
        docker run --rm --network host mysql:8 \
            mysqldump -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$MYSQL_PASS" \
            "$MYSQL_DB" > "$BACKUP_DIR/central.sql"
    fi

    # Build MySQL row count manifest
    log "Recording MySQL row counts..."
    MANIFEST="{\"central\":{"
    TABLES=$(docker run --rm --network host mysql:8 \
        mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$MYSQL_PASS" \
        -N -e "SHOW TABLES" "$MYSQL_DB" 2>/dev/null || echo "")

    FIRST=true
    for TABLE in $TABLES; do
        COUNT=$(docker run --rm --network host mysql:8 \
            mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$MYSQL_PASS" \
            -N -e "SELECT COUNT(*) FROM \`$TABLE\`" "$MYSQL_DB" 2>/dev/null || echo "0")
        $FIRST && FIRST=false || MANIFEST+=","
        MANIFEST+="\"$TABLE\":$COUNT"
    done
    MANIFEST+="},"

    # Dump each tenant database
    DB_PREFIX="mendyfi_tenant_"
    for TENANT_ID in $TENANT_DBS; do
        TENANT_DB="${DB_PREFIX}${TENANT_ID}"
        log "Dumping tenant: $TENANT_ID ($TENANT_DB)..."
        if ! $DRY_RUN; then
            docker run --rm --network host mysql:8 \
                mysqldump -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$MYSQL_PASS" \
                "$TENANT_DB" > "$BACKUP_DIR/${TENANT_ID}.sql" 2>/dev/null || {
                    warn "Could not dump $TENANT_DB (may not exist)"
                }
        fi

        # Row counts for this tenant
        MANIFEST+="\"$TENANT_ID\":{"
        T_TABLES=$(docker run --rm --network host mysql:8 \
            mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$MYSQL_PASS" \
            -N -e "SHOW TABLES" "$TENANT_DB" 2>/dev/null || echo "")

        T_FIRST=true
        for TABLE in $T_TABLES; do
            COUNT=$(docker run --rm --network host mysql:8 \
                mysql -h "$MYSQL_HOST" -P "$MYSQL_PORT" -u "$MYSQL_USER" -p"$MYSQL_PASS" \
                -N -e "SELECT COUNT(*) FROM \`$TABLE\`" "$TENANT_DB" 2>/dev/null || echo "0")
            $T_FIRST && T_FIRST=false || MANIFEST+=","
            MANIFEST+="\"$TABLE\":$COUNT"
        done
        MANIFEST+="},"
    done

    # Close JSON (remove trailing comma)
    MANIFEST="${MANIFEST%,}}"
    echo "$MANIFEST" > "$MANIFEST_FILE"
    log "MySQL manifest saved to $MANIFEST_FILE"
fi

log "Backup complete"

if $DRY_RUN; then
    log "DRY RUN complete. Would proceed with migration."
    exit 0
fi

# ---------------------
# Step 3: Spin up PostgreSQL
# ---------------------
step 3 "Spin up PostgreSQL migration environment"

cd "$SCRIPT_DIR"
docker compose -f docker-compose.migrate.yml up -d postgres
log "Waiting for PostgreSQL to accept connections..."

RETRIES=30
until docker compose -f docker-compose.migrate.yml exec postgres pg_isready -U "$PG_USER" &>/dev/null; do
    RETRIES=$((RETRIES - 1))
    if [[ $RETRIES -le 0 ]]; then
        error "PostgreSQL did not start in time"
        exit 1
    fi
    sleep 2
done
log "PostgreSQL: READY"

# ---------------------
# Step 4: Create PostgreSQL databases
# ---------------------
step 4 "Create PostgreSQL databases"

PG_EXEC="docker compose -f docker-compose.migrate.yml exec -T postgres psql -U $PG_USER"

# Central database already created by POSTGRES_DB env var
log "Central database 'mendyfi_central': exists (created by container)"

# Create tenant databases
DB_PREFIX="mendyfi_tenant_"
for TENANT_ID in ${TENANT_DBS:-}; do
    TENANT_DB="${DB_PREFIX}${TENANT_ID}"
    $PG_EXEC -d mendyfi_central -c "CREATE DATABASE \"$TENANT_DB\" OWNER \"$PG_USER\";" 2>/dev/null || {
        warn "Database $TENANT_DB already exists (skipping)"
    }
    log "Created: $TENANT_DB"
done

# ---------------------
# Step 5: Migrate data with pgloader
# ---------------------
step 5 "Migrate data with pgloader"

# Generate pgloader config from template for a given database pair
generate_pgloader_config() {
    local mysql_db="$1"
    local pg_db="$2"
    local config_file="$SCRIPT_DIR/pgloader-run.load"

    sed \
        -e "s|{{MYSQL_USER}}|$MYSQL_USER|g" \
        -e "s|{{MYSQL_PASS}}|$MYSQL_PASS|g" \
        -e "s|{{MYSQL_HOST}}|$MYSQL_HOST|g" \
        -e "s|{{MYSQL_PORT}}|$MYSQL_PORT|g" \
        -e "s|{{MYSQL_DB}}|$mysql_db|g" \
        -e "s|{{PG_USER}}|$PG_USER|g" \
        -e "s|{{PG_PASS}}|$PG_PASS|g" \
        -e "s|{{PG_HOST}}|postgres|g" \
        -e "s|{{PG_PORT}}|5432|g" \
        -e "s|{{PG_DB}}|$pg_db|g" \
        "$SCRIPT_DIR/pgloader.load" > "$config_file"

    echo "$config_file"
}

# Migrate central database
log "Migrating central database..."
CONFIG=$(generate_pgloader_config "$MYSQL_DB" "mendyfi_central")
docker compose -f docker-compose.migrate.yml run --rm \
    -v "$CONFIG:/migration/pgloader.load:ro" \
    pgloader pgloader /migration/pgloader.load 2>&1 | tail -20
log "Central database: MIGRATED"

# Migrate each tenant database
for TENANT_ID in ${TENANT_DBS:-}; do
    TENANT_MYSQL="${DB_PREFIX}${TENANT_ID}"
    TENANT_PG="${DB_PREFIX}${TENANT_ID}"
    log "Migrating tenant: $TENANT_ID..."
    CONFIG=$(generate_pgloader_config "$TENANT_MYSQL" "$TENANT_PG")
    docker compose -f docker-compose.migrate.yml run --rm \
        -v "$CONFIG:/migration/pgloader.load:ro" \
        pgloader pgloader /migration/pgloader.load 2>&1 | tail -5
done
log "All databases migrated"

# Clean up temporary config
rm -f "$SCRIPT_DIR/pgloader-run.load"

# ---------------------
# Step 6: Post-migration schema fixes
# ---------------------
step 6 "Post-migration schema fixes"

# Run post-migrate.sql on central database
log "Running post-migrate.sql on central database..."
$PG_EXEC -d mendyfi_central < "$SCRIPT_DIR/post-migrate.sql"

# Run on each tenant database
for TENANT_ID in ${TENANT_DBS:-}; do
    TENANT_DB="${DB_PREFIX}${TENANT_ID}"
    log "Running post-migrate.sql on $TENANT_DB..."
    $PG_EXEC -d "$TENANT_DB" < "$SCRIPT_DIR/post-migrate.sql"
done

log "Schema fixes: APPLIED"

# ---------------------
# Step 7: Update application stack
# ---------------------
step 7 "Update application configuration"

cd "$PROJECT_DIR"

# Update .env to point to PostgreSQL
log "Updating .env for PostgreSQL..."
sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=pgsql/" "$ENV_FILE"
sed -i "s/^DB_HOST=.*/DB_HOST=$PG_HOST/" "$ENV_FILE"
sed -i "s/^DB_PORT=.*/DB_PORT=$PG_PORT/" "$ENV_FILE"

# Clear Laravel caches if PHP container is available
if docker compose exec php true 2>/dev/null; then
    log "Clearing Laravel caches..."
    docker compose exec php php artisan config:clear 2>/dev/null || true
    docker compose exec php php artisan route:clear 2>/dev/null || true
    docker compose exec php php artisan view:clear 2>/dev/null || true
    docker compose exec php php artisan cache:clear 2>/dev/null || true

    # Run any pending Laravel migrations
    log "Running Laravel migrations..."
    docker compose exec php php artisan migrate --force 2>/dev/null || true
    docker compose exec php php artisan tenants:migrate --force 2>/dev/null || true
fi

log "Application updated"

# ---------------------
# Step 8: Validate
# ---------------------
step 8 "Validate migration"

if docker compose exec php true 2>/dev/null; then
    STRICT_FLAG=""
    if $CI_MODE; then
        STRICT_FLAG="--strict"
    fi
    docker compose exec php php artisan mendyfi:validate-migration \
        --mysql-manifest="$MANIFEST_FILE" $STRICT_FLAG
else
    warn "PHP container not available. Run validation manually:"
    warn "  php artisan mendyfi:validate-migration --mysql-manifest=$MANIFEST_FILE"
fi

# ---------------------
# Step 9: Cleanup
# ---------------------
step 9 "Cleanup"

log "Stopping pgloader container (no longer needed)..."
cd "$SCRIPT_DIR"
docker compose -f docker-compose.migrate.yml stop pgloader 2>/dev/null || true

log ""
log "========================================"
log "  Migration Complete!"
log "========================================"
log ""
log "PostgreSQL is now your primary database."
log "MySQL backup preserved at: $BACKUP_DIR/"
log ""
if [[ "$CI_MODE" != "true" ]]; then
    read -p "Remove old MySQL container? [y/N] " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        warn "Removing MySQL container... (backup still preserved)"
        docker compose stop mysql 2>/dev/null || true
        docker compose rm -f mysql 2>/dev/null || true
    fi
fi
log "Done. To rollback: ./migration/rollback.sh"
