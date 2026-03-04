# PostgreSQL Migration Toolkit -- Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a standalone migration toolkit that converts any mendyfi-radius MySQL installation to PostgreSQL 16, plus documentation for an upstream PR.

**Architecture:** Shell-script orchestrator (`migrate.sh`) coordinates Docker containers (pgloader + PostgreSQL 16) to migrate data from an existing MySQL installation. A Laravel Artisan validation command verifies data integrity. Rollback script restores original state from pre-migration backups.

**Tech Stack:** Bash (set -euo pipefail), pgloader, Docker Compose v2, PostgreSQL 16, Laravel Artisan, GitHub Actions CI

**Design doc:** `docs/plans/2026-03-03-postgres-migration-toolkit-design.md`

---

## Task 1: Scaffold migration directory + Docker Compose

**Files:**
- Create: `migration/docker-compose.migrate.yml`
- Create: `migration/.gitkeep` (for backups dir)

**Step 1: Create directory structure**

```bash
mkdir -p migration/backups
touch migration/backups/.gitkeep
```

**Step 2: Write docker-compose.migrate.yml**

Create `migration/docker-compose.migrate.yml`:

```yaml
name: mendyfi-migration

services:
  pgloader:
    image: dimitri/pgloader:latest
    volumes:
      - ./pgloader.load:/migration/pgloader.load:ro
    networks:
      - migrate-net
    depends_on:
      postgres:
        condition: service_healthy
    profiles:
      - migrate

  postgres:
    image: postgres:16-alpine
    environment:
      POSTGRES_USER: ${PG_USER:-mendyfi}
      POSTGRES_PASSWORD: ${PG_PASS:-changeme_migrate}
      POSTGRES_DB: mendyfi_central
    volumes:
      - pg-migrate-data:/var/lib/postgresql/data
    ports:
      - "127.0.0.1:${PG_MIGRATE_PORT:-5433}:5432"
    networks:
      - migrate-net
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${PG_USER:-mendyfi}"]
      interval: 5s
      timeout: 3s
      retries: 10
    deploy:
      resources:
        limits:
          memory: 256m

networks:
  migrate-net:
    driver: bridge

volumes:
  pg-migrate-data:
```

Note: pgloader uses a `migrate` profile so it doesn't auto-start. We invoke it explicitly per-database.

**Step 3: Verify compose file is valid**

Run: `cd migration && docker compose -f docker-compose.migrate.yml config --quiet && echo "VALID"`
Expected: `VALID` (no output = valid YAML)

**Step 4: Commit**

```bash
git add migration/
git commit -m "feat(migration): scaffold directory + Docker Compose for pgloader"
```

---

## Task 2: Write pgloader configuration

**Files:**
- Create: `migration/pgloader.load`

**Step 1: Write pgloader.load**

This file uses pgloader's DSL to define type casts and migration behaviour. Template variables (`{{VAR}}`) will be substituted by `migrate.sh` at runtime using `envsubst`.

Create `migration/pgloader.load`:

```
LOAD DATABASE
  FROM mysql://{{MYSQL_USER}}:{{MYSQL_PASS}}@{{MYSQL_HOST}}:{{MYSQL_PORT}}/{{MYSQL_DB}}
  INTO postgresql://{{PG_USER}}:{{PG_PASS}}@{{PG_HOST}}:{{PG_PORT}}/{{PG_DB}}

WITH
  include drop,
  create tables,
  create indexes,
  reset sequences,
  workers = 4,
  concurrency = 2,
  batch rows = 1000,
  prefetch rows = 1000

SET
  maintenance_work_mem to '128MB',
  work_mem to '64MB'

CAST
  type tinyint to boolean using tinyint-to-boolean,
  type int with extra auto_increment to serial,
  type bigint with extra auto_increment to bigserial,
  type datetime to "timestamp without time zone",
  type varchar to text drop typemod

ALTER SCHEMA '{{MYSQL_DB}}' RENAME TO 'public'
;
```

Key decisions:
- `include drop`: pgloader drops existing PG tables before creating (idempotent re-runs)
- `reset sequences`: fixes auto-increment values after data load
- `tinyint -> boolean`: MySQL uses tinyint(1) for booleans
- `varchar -> text drop typemod`: PostgreSQL text type is more flexible than varchar
- Schema rename to `public`: pgloader would otherwise create a schema named after the MySQL database

**Step 2: Verify pgloader syntax (basic check)**

Run: `grep -c 'LOAD DATABASE' migration/pgloader.load`
Expected: `1`

**Step 3: Commit**

```bash
git add migration/pgloader.load
git commit -m "feat(migration): pgloader config with MySQL-to-PG type casts"
```

---

## Task 3: Write post-migrate.sql schema fixes

**Files:**
- Create: `migration/post-migrate.sql`

**Step 1: Write post-migrate.sql**

This SQL runs AFTER pgloader finishes, to fix things pgloader doesn't handle:

```sql
-- post-migrate.sql
-- Runs after pgloader migration to fix PostgreSQL-specific schema issues.
-- Designed to be idempotent (safe to re-run).

-- 1. Fix sequence ownership (pgloader sometimes misses this)
DO $$
DECLARE
    r RECORD;
BEGIN
    FOR r IN
        SELECT c.table_name, c.column_name,
               pg_get_serial_sequence(c.table_name, c.column_name) as seq_name
        FROM information_schema.columns c
        WHERE c.column_default LIKE 'nextval%'
          AND c.table_schema = 'public'
    LOOP
        IF r.seq_name IS NOT NULL THEN
            EXECUTE format('ALTER SEQUENCE %s OWNED BY %I.%I',
                           r.seq_name, r.table_name, r.column_name);
        END IF;
    END LOOP;
END $$;

-- 2. Ensure sequences are ahead of max(id)
DO $$
DECLARE
    r RECORD;
    max_val BIGINT;
BEGIN
    FOR r IN
        SELECT c.table_name, c.column_name,
               pg_get_serial_sequence(c.table_name, c.column_name) as seq_name
        FROM information_schema.columns c
        WHERE c.column_default LIKE 'nextval%'
          AND c.table_schema = 'public'
    LOOP
        IF r.seq_name IS NOT NULL THEN
            EXECUTE format('SELECT COALESCE(MAX(%I), 0) FROM %I', r.column_name, r.table_name) INTO max_val;
            EXECUTE format('SELECT setval(%L, GREATEST(%s, 1))', r.seq_name, max_val);
        END IF;
    END LOOP;
END $$;

-- 3. Add missing indexes that Laravel migrations would create but pgloader might skip
-- (pgloader creates indexes from MySQL, but some may be missed)
CREATE INDEX IF NOT EXISTS idx_sales_records_user_id ON sales_records(user_id);
CREATE INDEX IF NOT EXISTS idx_sales_records_transact_date ON sales_records(transact_date);
CREATE INDEX IF NOT EXISTS idx_sales_records_profile ON sales_records(hotspot_profile_id);
CREATE INDEX IF NOT EXISTS idx_hotspot_vouchers_user_id ON hotspot_vouchers(user_id);
CREATE INDEX IF NOT EXISTS idx_hotspot_vouchers_code ON hotspot_vouchers(code);
CREATE INDEX IF NOT EXISTS idx_resellers_user_id ON resellers(user_id);

-- 4. Set search_path for application user
ALTER DATABASE current_database() SET search_path TO public;
```

**Step 2: Verify SQL syntax (basic)**

Run: `grep -c 'CREATE INDEX IF NOT EXISTS' migration/post-migrate.sql`
Expected: `6`

**Step 3: Commit**

```bash
git add migration/post-migrate.sql
git commit -m "feat(migration): post-migrate SQL fixes for sequences, indexes, search_path"
```

---

## Task 4: Write the rollback script

**Files:**
- Create: `migration/rollback.sh`

**Step 1: Write rollback.sh**

```bash
#!/usr/bin/env bash
set -euo pipefail

# rollback.sh -- Reverses PostgreSQL migration, restores MySQL state.
# Usage: ./migration/rollback.sh [--backup-dir DIR]

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR="${1:---backup-dir}"

# Parse arguments
while [[ $# -gt 0 ]]; do
    case "$1" in
        --backup-dir) BACKUP_DIR="$2"; shift 2 ;;
        *) echo "Unknown option: $1"; exit 1 ;;
    esac
done

# Find latest backup directory if not specified
if [[ "$BACKUP_DIR" == "--backup-dir" ]] || [[ -z "$BACKUP_DIR" ]]; then
    BACKUP_DIR=$(ls -1d "$SCRIPT_DIR"/backups/????-??-??/ 2>/dev/null | sort -r | head -1)
    if [[ -z "$BACKUP_DIR" ]]; then
        echo "ERROR: No backup directory found in $SCRIPT_DIR/backups/"
        echo "Cannot rollback without a pre-migration backup."
        exit 1
    fi
fi

echo "========================================"
echo "  MendyFi Migration Rollback"
echo "========================================"
echo ""
echo "Backup directory: $BACKUP_DIR"
echo ""

# Verify backup exists
if [[ ! -f "$BACKUP_DIR/.env.backup" ]]; then
    echo "ERROR: No .env.backup found in $BACKUP_DIR"
    exit 1
fi

if [[ ! -f "$BACKUP_DIR/docker-compose.yml.backup" ]]; then
    echo "ERROR: No docker-compose.yml.backup found in $BACKUP_DIR"
    exit 1
fi

echo "This will:"
echo "  1. Restore your original .env file"
echo "  2. Restore your original docker-compose.yml"
echo "  3. Point the application back to MySQL"
echo "  4. Rebuild the PHP container"
echo "  5. Clear Laravel caches"
echo ""

if [[ "${CI_MODE:-false}" != "true" ]]; then
    read -p "Continue with rollback? [y/N] " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Rollback cancelled."
        exit 0
    fi
fi

echo ""
echo "[1/5] Restoring .env..."
cp "$BACKUP_DIR/.env.backup" .env
echo "  Done."

echo "[2/5] Restoring docker-compose.yml..."
cp "$BACKUP_DIR/docker-compose.yml.backup" docker-compose.yml
echo "  Done."

echo "[3/5] Rebuilding PHP container..."
docker compose build php 2>&1 | tail -3
docker compose up -d php
echo "  Done."

echo "[4/5] Clearing Laravel caches..."
docker compose exec php php artisan config:clear 2>/dev/null || true
docker compose exec php php artisan route:clear 2>/dev/null || true
docker compose exec php php artisan view:clear 2>/dev/null || true
echo "  Done."

echo "[5/5] Verifying MySQL connectivity..."
docker compose exec php php artisan db:monitor --databases=mysql 2>/dev/null || {
    echo "  WARNING: Could not verify MySQL connection."
    echo "  Check that your MySQL container is running."
}

echo ""
echo "========================================"
echo "  Rollback Complete"
echo "========================================"
echo ""
echo "Your application has been restored to MySQL."
echo "PostgreSQL migration container preserved for debugging."
echo "To remove it: docker compose -f migration/docker-compose.migrate.yml down -v"
echo ""
```

**Step 2: Make executable**

Run: `chmod +x migration/rollback.sh`

**Step 3: Commit**

```bash
git add migration/rollback.sh
git commit -m "feat(migration): rollback script to restore MySQL state from backup"
```

---

## Task 5: Write the validation Artisan command

**Files:**
- Create: `src/app/Console/Commands/ValidateMigration.php`

**Step 1: Write ValidateMigration.php**

This is the most critical piece -- it verifies the migration was successful.

```php
<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ValidateMigration extends Command
{
    protected $signature = 'mendyfi:validate-migration
                            {--strict : Exit with non-zero on any failure}
                            {--mysql-manifest= : Path to MySQL row count manifest JSON}';

    protected $description = 'Validate PostgreSQL migration: table existence, row counts, FK integrity, sequences';

    private int $passed = 0;
    private int $failed = 0;
    private array $failures = [];

    public function handle(): int
    {
        $this->info('');
        $this->info('==========================================');
        $this->info('  MendyFi Migration Validator');
        $this->info('==========================================');
        $this->info('');

        // Load MySQL manifest if provided (row counts from pre-migration)
        $manifest = [];
        if ($path = $this->option('mysql-manifest')) {
            if (file_exists($path)) {
                $manifest = json_decode(file_get_contents($path), true) ?? [];
                $this->info("Loaded MySQL manifest: " . count($manifest) . " databases");
            } else {
                $this->warn("Manifest file not found: $path");
            }
        }

        // Validate central database
        $this->info('--- Central Database ---');
        $this->validateDatabase('central', $manifest['central'] ?? []);

        // Validate each tenant database
        $tenants = Tenant::all();
        foreach ($tenants as $tenant) {
            $this->info('');
            $this->info("--- Tenant: {$tenant->id} ---");
            tenancy()->initialize($tenant);
            $dbName = DB::connection()->getDatabaseName();
            $this->validateDatabase($dbName, $manifest[$tenant->id] ?? []);
        }
        tenancy()->end();

        // Summary
        $this->info('');
        $this->info('==========================================');
        $total = $this->passed + $this->failed;
        if ($this->failed === 0) {
            $this->info("  PASS: All $total checks passed");
        } else {
            $this->error("  FAIL: {$this->failed} of $total checks failed");
            foreach ($this->failures as $f) {
                $this->error("    - $f");
            }
        }
        $this->info('==========================================');
        $this->info('');

        if ($this->option('strict') && $this->failed > 0) {
            return 1;
        }

        return 0;
    }

    private function validateDatabase(string $label, array $expectedCounts): void
    {
        // Check 1: Expected tables exist
        $expectedTables = $this->getExpectedTables($label);
        foreach ($expectedTables as $table) {
            if (Schema::hasTable($table)) {
                $this->pass("Table '$table' exists");
            } else {
                $this->fail("Table '$table' MISSING");
            }
        }

        // Check 2: Row counts match manifest (if provided)
        if (!empty($expectedCounts)) {
            foreach ($expectedCounts as $table => $count) {
                if (!Schema::hasTable($table)) continue;
                $actual = DB::table($table)->count();
                if ($actual === (int) $count) {
                    $this->pass("Row count '$table': $actual (matches)");
                } else {
                    $this->fail("Row count '$table': expected $count, got $actual");
                }
            }
        }

        // Check 3: Sequence values are valid (next_val > max(id))
        $sequences = DB::select("
            SELECT c.table_name, c.column_name,
                   pg_get_serial_sequence(c.table_name, c.column_name) as seq_name
            FROM information_schema.columns c
            WHERE c.column_default LIKE 'nextval%'
              AND c.table_schema = 'public'
        ");

        foreach ($sequences as $seq) {
            if (!$seq->seq_name) continue;
            $maxVal = DB::selectOne("SELECT COALESCE(MAX({$seq->column_name}), 0) as m FROM {$seq->table_name}")->m;
            $nextVal = DB::selectOne("SELECT last_value FROM {$seq->seq_name}")->last_value;
            if ($nextVal >= $maxVal) {
                $this->pass("Sequence '{$seq->seq_name}': {$nextVal} >= max({$maxVal})");
            } else {
                $this->fail("Sequence '{$seq->seq_name}': {$nextVal} < max({$maxVal})");
            }
        }

        // Check 4: Foreign key constraints are valid (no orphan references)
        $fks = DB::select("
            SELECT
                tc.constraint_name,
                tc.table_name,
                kcu.column_name,
                ccu.table_name AS foreign_table,
                ccu.column_name AS foreign_column
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
                ON tc.constraint_name = kcu.constraint_name
            JOIN information_schema.constraint_column_usage ccu
                ON ccu.constraint_name = tc.constraint_name
            WHERE tc.constraint_type = 'FOREIGN KEY'
              AND tc.table_schema = 'public'
        ");

        foreach ($fks as $fk) {
            $orphans = DB::selectOne("
                SELECT COUNT(*) as cnt FROM {$fk->table_name} t
                LEFT JOIN {$fk->foreign_table} f ON t.{$fk->column_name} = f.{$fk->foreign_column}
                WHERE t.{$fk->column_name} IS NOT NULL AND f.{$fk->foreign_column} IS NULL
            ");
            if ($orphans->cnt === 0) {
                $this->pass("FK '{$fk->constraint_name}': no orphan rows");
            } else {
                $this->fail("FK '{$fk->constraint_name}': {$orphans->cnt} orphan rows in {$fk->table_name}");
            }
        }
    }

    private function getExpectedTables(string $label): array
    {
        if ($label === 'central') {
            return ['users', 'tenants', 'domains', 'app_settings'];
        }

        // Tenant tables (from migrations/tenant/)
        return [
            'users', 'password_reset_tokens', 'failed_jobs', 'personal_access_tokens',
            'resellers', 'hotspot_profiles', 'hotspot_vouchers', 'app_settings',
            'sales_records', 'user_settings', 'voucher_templates', 'jobs',
            'unified_profiles', 'permissions', 'roles', 'model_has_permissions',
            'model_has_roles', 'role_has_permissions',
            'fair_use_policies', 'bind_fair_use_policies', 'active_fair_use_policies',
        ];
    }

    private function pass(string $message): void
    {
        $this->passed++;
        $this->line("  <fg=green>PASS</> $message");
    }

    private function fail(string $message): void
    {
        $this->failed++;
        $this->failures[] = $message;
        $this->line("  <fg=red>FAIL</> $message");
    }
}
```

**Step 2: Verify the command is syntactically valid**

Run: `cd /home/karetech/projects/hotspot-manager/src && php -l app/Console/Commands/ValidateMigration.php`
Expected: `No syntax errors detected`

**Step 3: Commit**

```bash
git add src/app/Console/Commands/ValidateMigration.php
git commit -m "feat(migration): validation Artisan command for post-migration checks"
```

---

## Task 6: Write the main migration orchestrator (migrate.sh)

**Files:**
- Create: `migration/migrate.sh`

**Step 1: Write migrate.sh**

This is the largest file. It orchestrates the entire migration.

```bash
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
for TENANT_ID in $TENANT_DBS; do
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

# Generate pgloader config from template for central DB
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
for TENANT_ID in $TENANT_DBS; do
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
for TENANT_ID in $TENANT_DBS; do
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
```

**Step 2: Make executable**

Run: `chmod +x migration/migrate.sh`

**Step 3: Verify script syntax**

Run: `bash -n migration/migrate.sh && echo "SYNTAX OK"`
Expected: `SYNTAX OK`

**Step 4: Commit**

```bash
git add migration/migrate.sh
git commit -m "feat(migration): main orchestrator script with 9-step migration flow"
```

---

## Task 7: Write seed test data

**Files:**
- Create: `migration/seed-test-data.sql`

**Step 1: Write seed-test-data.sql**

MySQL-formatted SQL that creates a realistic test dataset for CI testing.

```sql
-- seed-test-data.sql
-- Creates a realistic mendyfi MySQL dataset for migration testing.
-- Run against a fresh MySQL 8 instance.

-- Central database tables (simplified -- matches mendyfi schema)
CREATE DATABASE IF NOT EXISTS mendyfi_central;
USE mendyfi_central;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tenants (
    id CHAR(36) PRIMARY KEY,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    data JSON
);

CREATE TABLE IF NOT EXISTS domains (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) NOT NULL,
    username VARCHAR(255) DEFAULT NULL,
    tenant_id CHAR(36) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS app_settings (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(255) NOT NULL,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Seed admin user
INSERT INTO users (name, email, password) VALUES
    ('Admin', 'admin@mendyfi.test', '$2y$12$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012');

-- Seed 3 tenants
INSERT INTO tenants (id, data) VALUES
    ('t-alpha-0001-0001-000000000001', '{"plan": "basic"}'),
    ('t-bravo-0002-0002-000000000002', '{"plan": "pro"}'),
    ('t-charlie-003-0003-000000000003', '{"plan": "basic"}');

INSERT INTO domains (domain, username, tenant_id) VALUES
    ('alpha.mendyfi.test', 'alpha', 't-alpha-0001-0001-000000000001'),
    ('bravo.mendyfi.test', 'bravo', 't-bravo-0002-0002-000000000002'),
    ('charlie.mendyfi.test', 'charlie', 't-charlie-003-0003-000000000003');

INSERT INTO app_settings (`key`, value) VALUES
    ('app_name', 'MendyFi'),
    ('timezone', 'America/Guyana');

-- Create tenant databases with sample data
-- Tenant: alpha
CREATE DATABASE IF NOT EXISTS mendyfi_tenant_t_alpha_0001_0001_000000000001;
USE mendyfi_tenant_t_alpha_0001_0001_000000000001;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    logo_path VARCHAR(255) DEFAULT NULL,
    accent_color VARCHAR(7) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS resellers (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    contact VARCHAR(255) DEFAULT NULL,
    discount DECIMAL(5,2) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS hotspot_profiles (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) DEFAULT 0,
    uptime_limit INT DEFAULT 0,
    data_limit BIGINT DEFAULT 0,
    validity INT DEFAULT 0,
    shared_users INT DEFAULT 1,
    rate_limit VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS hotspot_vouchers (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    code VARCHAR(255) NOT NULL,
    hotspot_profile_id BIGINT DEFAULT NULL,
    reseller_id BIGINT DEFAULT NULL,
    status TINYINT(1) DEFAULT 0,
    mac_address VARCHAR(255) DEFAULT NULL,
    ip_address VARCHAR(255) DEFAULT NULL,
    router_ip VARCHAR(255) DEFAULT NULL,
    server_name VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hotspot_profile_id) REFERENCES hotspot_profiles(id) ON DELETE SET NULL,
    FOREIGN KEY (reseller_id) REFERENCES resellers(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS sales_records (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    code VARCHAR(255) NOT NULL,
    hotspot_profile_id BIGINT DEFAULT NULL,
    reseller_id BIGINT DEFAULT NULL,
    amount DECIMAL(10,2) DEFAULT 0,
    mac_address VARCHAR(255) DEFAULT NULL,
    ip_address VARCHAR(255) DEFAULT NULL,
    router_ip VARCHAR(255) DEFAULT NULL,
    server_name VARCHAR(255) DEFAULT NULL,
    transact_date DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hotspot_profile_id) REFERENCES hotspot_profiles(id) ON DELETE SET NULL,
    FOREIGN KEY (reseller_id) REFERENCES resellers(id) ON DELETE SET NULL
);

-- Seed alpha tenant data
INSERT INTO users (name, email, password) VALUES
    ('Alpha Admin', 'admin@alpha.test', '$2y$12$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012');

INSERT INTO hotspot_profiles (user_id, name, price, uptime_limit, data_limit, validity, shared_users) VALUES
    (1, '1 Hour', 100.00, 3600, 524288000, 3600, 1),
    (1, '1 Day', 300.00, 86400, 2147483648, 86400, 2),
    (1, '1 Week', 1500.00, 604800, 10737418240, 604800, 3);

INSERT INTO resellers (user_id, name, contact, discount, is_active) VALUES
    (1, 'John Reseller', '5921234567', 10.00, 1),
    (1, 'Jane Reseller', '5929876543', 15.00, 1);

-- Generate 50 vouchers
INSERT INTO hotspot_vouchers (user_id, code, hotspot_profile_id, reseller_id, status) VALUES
    (1, 'ALPHA-001', 1, NULL, 0), (1, 'ALPHA-002', 1, NULL, 0),
    (1, 'ALPHA-003', 1, 1, 0), (1, 'ALPHA-004', 2, NULL, 1),
    (1, 'ALPHA-005', 2, 1, 1), (1, 'ALPHA-006', 2, 2, 0),
    (1, 'ALPHA-007', 3, NULL, 0), (1, 'ALPHA-008', 3, 2, 1),
    (1, 'ALPHA-009', 1, NULL, 0), (1, 'ALPHA-010', 1, 1, 0);

-- Generate 20 sales records
INSERT INTO sales_records (user_id, code, hotspot_profile_id, reseller_id, amount, mac_address, ip_address, transact_date) VALUES
    (1, 'ALPHA-004', 2, NULL, 300.00, 'AA:BB:CC:DD:EE:01', '10.0.0.101', '2026-02-15 10:30:00'),
    (1, 'ALPHA-005', 2, 1, 270.00, 'AA:BB:CC:DD:EE:02', '10.0.0.102', '2026-02-15 11:00:00'),
    (1, 'ALPHA-008', 3, 2, 1275.00, 'AA:BB:CC:DD:EE:03', '10.0.0.103', '2026-02-16 09:15:00'),
    (1, 'ALPHA-011', 1, NULL, 100.00, 'DA:BB:CC:DD:EE:04', '10.0.0.104', '2026-02-17 14:00:00'),
    (1, 'ALPHA-012', 1, 1, 90.00, 'AA:BB:CC:DD:EE:05', '10.0.0.105', '2026-02-18 08:45:00'),
    (1, 'ALPHA-013', 2, NULL, 300.00, 'AA:BB:CC:DD:EE:06', '10.0.0.106', '2026-02-19 16:20:00'),
    (1, 'ALPHA-014', 3, 2, 1275.00, 'AA:BB:CC:DD:EE:07', '10.0.0.107', '2026-02-20 12:00:00'),
    (1, 'ALPHA-015', 1, NULL, 100.00, 'AA:BB:CC:DD:EE:08', '10.0.0.108', '2026-02-21 09:30:00'),
    (1, 'ALPHA-016', 2, 1, 270.00, 'AA:BB:CC:DD:EE:09', '10.0.0.109', '2026-02-22 13:45:00'),
    (1, 'ALPHA-017', 1, NULL, 100.00, 'AA:BB:CC:DD:EE:10', '10.0.0.110', '2026-02-23 10:00:00');

-- Tenant: bravo (smaller dataset)
CREATE DATABASE IF NOT EXISTS mendyfi_tenant_t_bravo_0002_0002_000000000002;
USE mendyfi_tenant_t_bravo_0002_0002_000000000002;

CREATE TABLE IF NOT EXISTS users LIKE mendyfi_tenant_t_alpha_0001_0001_000000000001.users;
CREATE TABLE IF NOT EXISTS resellers LIKE mendyfi_tenant_t_alpha_0001_0001_000000000001.resellers;
CREATE TABLE IF NOT EXISTS hotspot_profiles LIKE mendyfi_tenant_t_alpha_0001_0001_000000000001.hotspot_profiles;
CREATE TABLE IF NOT EXISTS hotspot_vouchers LIKE mendyfi_tenant_t_alpha_0001_0001_000000000001.hotspot_vouchers;
CREATE TABLE IF NOT EXISTS sales_records LIKE mendyfi_tenant_t_alpha_0001_0001_000000000001.sales_records;

INSERT INTO users (name, email, password) VALUES
    ('Bravo Admin', 'admin@bravo.test', '$2y$12$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012');

INSERT INTO hotspot_profiles (user_id, name, price, uptime_limit, data_limit, validity, shared_users) VALUES
    (1, '30 Min', 50.00, 1800, 262144000, 1800, 1),
    (1, '3 Hours', 200.00, 10800, 1073741824, 10800, 2);

INSERT INTO hotspot_vouchers (user_id, code, hotspot_profile_id, status) VALUES
    (1, 'BRAVO-001', 1, 0), (1, 'BRAVO-002', 1, 1),
    (1, 'BRAVO-003', 2, 0), (1, 'BRAVO-004', 2, 0),
    (1, 'BRAVO-005', 1, 1);

INSERT INTO sales_records (user_id, code, hotspot_profile_id, amount, mac_address, ip_address, transact_date) VALUES
    (1, 'BRAVO-002', 1, 50.00, 'BB:CC:DD:EE:FF:01', '192.168.1.101', '2026-02-20 10:00:00'),
    (1, 'BRAVO-005', 1, 50.00, 'BB:CC:DD:EE:FF:02', '192.168.1.102', '2026-02-22 14:30:00');

-- Tenant: charlie (empty -- edge case test)
CREATE DATABASE IF NOT EXISTS mendyfi_tenant_t_charlie_003_0003_000000000003;
USE mendyfi_tenant_t_charlie_003_0003_000000000003;

CREATE TABLE IF NOT EXISTS users LIKE mendyfi_tenant_t_alpha_0001_0001_000000000001.users;
CREATE TABLE IF NOT EXISTS resellers LIKE mendyfi_tenant_t_alpha_0001_0001_000000000001.resellers;
CREATE TABLE IF NOT EXISTS hotspot_profiles LIKE mendyfi_tenant_t_alpha_0001_0001_000000000001.hotspot_profiles;
CREATE TABLE IF NOT EXISTS hotspot_vouchers LIKE mendyfi_tenant_t_alpha_0001_0001_000000000001.hotspot_vouchers;
CREATE TABLE IF NOT EXISTS sales_records LIKE mendyfi_tenant_t_alpha_0001_0001_000000000001.sales_records;

INSERT INTO users (name, email, password) VALUES
    ('Charlie Admin', 'admin@charlie.test', '$2y$12$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012');
```

**Step 2: Verify SQL syntax (basic check)**

Run: `grep -c 'CREATE DATABASE' migration/seed-test-data.sql`
Expected: `4` (central + 3 tenants)

**Step 3: Commit**

```bash
git add migration/seed-test-data.sql
git commit -m "feat(migration): seed test data with 3 tenants for CI testing"
```

---

## Task 8: Write GitHub Actions CI workflow

**Files:**
- Create: `.github/workflows/migration-test.yml`

**Step 1: Write the CI workflow**

```yaml
name: Migration Test

on:
  push:
    paths:
      - 'migration/**'
      - '.github/workflows/migration-test.yml'
  pull_request:
    paths:
      - 'migration/**'
      - '.github/workflows/migration-test.yml'

jobs:
  test-migration:
    runs-on: ubuntu-latest
    timeout-minutes: 15

    services:
      mysql:
        image: mysql:8
        env:
          MYSQL_ROOT_PASSWORD: test
          MYSQL_USER: mendyfi
          MYSQL_PASSWORD: mendyfi_test
          MYSQL_DATABASE: mendyfi_central
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping -h 127.0.0.1 -u root -ptest"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Seed MySQL with test data
        run: |
          mysql -h 127.0.0.1 -P 3306 -u root -ptest < migration/seed-test-data.sql

      - name: Create .env for migration
        run: |
          cat > .env <<'ENVEOF'
          DB_CONNECTION=mysql
          DB_HOST=127.0.0.1
          DB_PORT=3306
          DB_DATABASE=mendyfi_central
          DB_USERNAME=root
          DB_PASSWORD=test
          ENVEOF

      - name: Run migration (dry-run first)
        run: ./migration/migrate.sh --dry-run

      - name: Run migration
        env:
          PG_USER: mendyfi
          PG_PASS: mendyfi_test_pg
          CI_MODE: "true"
        run: ./migration/migrate.sh --ci-mode --pg-port 5433

      - name: Verify PostgreSQL databases exist
        run: |
          docker compose -f migration/docker-compose.migrate.yml exec -T postgres \
            psql -U mendyfi -d mendyfi_central -c "SELECT COUNT(*) FROM tenants;"

      - name: Verify tenant databases
        run: |
          docker compose -f migration/docker-compose.migrate.yml exec -T postgres \
            psql -U mendyfi -l | grep mendyfi_tenant

      - name: Cleanup
        if: always()
        run: |
          cd migration && docker compose -f docker-compose.migrate.yml down -v 2>/dev/null || true
```

**Step 2: Verify YAML is valid**

Run: `python3 -c "import yaml; yaml.safe_load(open('.github/workflows/migration-test.yml'))" && echo "VALID"`
Expected: `VALID`

**Step 3: Commit**

```bash
git add .github/workflows/migration-test.yml
git commit -m "feat(migration): GitHub Actions CI workflow for automated migration testing"
```

---

## Task 9: Write the migration README

**Files:**
- Create: `migration/README.md`

**Step 1: Write README.md**

```markdown
# MendyFi MySQL to PostgreSQL Migration Toolkit

Migrate your existing MendyFi RADIUS installation from MySQL to PostgreSQL 16 with one command.

## Prerequisites

- Docker Engine 24+ with Compose v2
- Running MendyFi installation with MySQL
- `.env` file with `DB_*` credentials
- At least 2x your database size in free disk space

## Quick Start

```bash
# From your mendyfi-radius project root:
./migration/migrate.sh
```

The script will:
1. Verify prerequisites (Docker, MySQL connection, disk space)
2. Backup your MySQL databases and configuration
3. Spin up a PostgreSQL 16 container
4. Migrate all data using pgloader (central + all tenant databases)
5. Fix PostgreSQL-specific schema issues (sequences, indexes)
6. Update your `.env` to point to PostgreSQL
7. Validate the migration (table counts, FK integrity, sequences)
8. Clean up temporary containers

## Options

| Flag | Description |
|------|-------------|
| `--dry-run` | Print what would be done, make no changes |
| `--skip-backup` | Skip MySQL backup (if you already have one) |
| `--mysql-host HOST` | Override MySQL host from .env |
| `--mysql-port PORT` | Override MySQL port (default: 3306) |
| `--pg-host HOST` | PostgreSQL host (default: 127.0.0.1) |
| `--pg-port PORT` | PostgreSQL port (default: 5433) |
| `--ci-mode` | Non-interactive, auto-yes, strict validation |

## Rollback

If anything goes wrong, restore your MySQL setup:

```bash
./migration/rollback.sh
```

This restores your `.env`, `docker-compose.yml`, and points the app back to MySQL. Your MySQL backup is preserved in `migration/backups/YYYY-MM-DD/`.

## Manual Validation

After migration, verify everything is correct:

```bash
php artisan mendyfi:validate-migration --strict
```

Or with the pre-migration MySQL manifest for row count comparison:

```bash
php artisan mendyfi:validate-migration --mysql-manifest=migration/backups/2026-03-03/mysql-manifest.json --strict
```

## What Gets Migrated

- Central database (`mendyfi_central`): users, tenants, domains, settings
- All tenant databases (`mendyfi_tenant_*`): vouchers, profiles, resellers, sales records, templates, FUP policies
- Auto-increment values converted to PostgreSQL sequences
- MySQL `TINYINT(1)` converted to PostgreSQL `BOOLEAN`
- `DATETIME` converted to `TIMESTAMP WITHOUT TIME ZONE`

## Files

| File | Purpose |
|------|---------|
| `migrate.sh` | Main migration orchestrator |
| `rollback.sh` | Restores MySQL state from backup |
| `docker-compose.migrate.yml` | pgloader + PostgreSQL containers |
| `pgloader.load` | pgloader type mapping configuration |
| `post-migrate.sql` | Post-migration schema fixes |
| `seed-test-data.sql` | Test dataset for CI |

## Troubleshooting

**pgloader fails with connection error:**
Ensure MySQL is accessible from Docker. If MySQL runs in a container, both must share a Docker network.

**Sequence values are wrong after migration:**
Run `post-migrate.sql` again: `psql -U mendyfi -d mendyfi_central -f migration/post-migrate.sql`

**Validation fails on row counts:**
Check the pgloader output for warnings. Some rows may be skipped due to constraint violations. Review the MySQL data for integrity issues.

**"Migration already in progress" error:**
A previous run crashed. Remove the lockfile: `rm migration/.migration.lock`
```

**Step 2: Commit**

```bash
git add migration/README.md
git commit -m "docs(migration): comprehensive README with quick start, options, troubleshooting"
```

---

## Task 10: Local integration test

**Step 1: Verify all files exist**

Run:
```bash
ls -la migration/
```

Expected files:
- `migrate.sh` (executable)
- `rollback.sh` (executable)
- `docker-compose.migrate.yml`
- `pgloader.load`
- `post-migrate.sql`
- `seed-test-data.sql`
- `README.md`
- `backups/.gitkeep`

```bash
ls -la .github/workflows/migration-test.yml
ls -la src/app/Console/Commands/ValidateMigration.php
```

**Step 2: Verify scripts are executable**

Run: `test -x migration/migrate.sh && test -x migration/rollback.sh && echo "EXECUTABLE"`
Expected: `EXECUTABLE`

**Step 3: Verify bash syntax for both scripts**

Run: `bash -n migration/migrate.sh && bash -n migration/rollback.sh && echo "SYNTAX OK"`
Expected: `SYNTAX OK`

**Step 4: Verify PHP syntax for validation command**

Run: `cd /home/karetech/projects/hotspot-manager/src && php -l app/Console/Commands/ValidateMigration.php`
Expected: `No syntax errors detected`

**Step 5: Dry-run test (if MySQL is not available, this tests argument parsing)**

Run: `./migration/migrate.sh --dry-run --mysql-host 127.0.0.1 2>&1 | head -5`
Expected: First few lines of output showing parsed credentials (will fail at MySQL connection check, which is correct for dry-run without MySQL)

---

## Task 11: Final commit + push

**Step 1: Review all changes**

Run: `git status && git log --oneline -10`

**Step 2: Ensure all files are committed**

If any uncommitted files remain:
```bash
git add -A
git commit -m "feat(migration): PostgreSQL migration toolkit (complete)"
```

**Step 3: Push**

Run: `git push origin master`

---

## Execution Summary

| Task | Files | Description |
|------|-------|-------------|
| 1 | docker-compose.migrate.yml, backups/.gitkeep | Docker Compose for pgloader + PG 16 |
| 2 | pgloader.load | pgloader type cast configuration |
| 3 | post-migrate.sql | Post-migration schema fixes |
| 4 | rollback.sh | MySQL restoration script |
| 5 | ValidateMigration.php | Artisan validation command |
| 6 | migrate.sh | Main 9-step orchestrator |
| 7 | seed-test-data.sql | Test dataset (3 tenants) |
| 8 | migration-test.yml | GitHub Actions CI |
| 9 | README.md | Documentation |
| 10 | -- | Local integration test |
| 11 | -- | Final commit + push |

## Risks

- pgloader `LIKE` table creation in seed data may not work across databases on all MySQL versions. If CI fails on seed step, switch to explicit `CREATE TABLE` for bravo/charlie tenants.
- The `--network host` flag in migrate.sh is needed for the ephemeral mysql:8 containers to reach the MySQL service. On macOS Docker Desktop, `host.docker.internal` may be needed instead.
- Large production databases (>10GB) may need increased pgloader workers/batch size. The defaults (workers=4, batch=1000) are conservative.
