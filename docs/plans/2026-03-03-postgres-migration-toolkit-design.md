# Mendyfi PostgreSQL Migration Toolkit -- Design Document

**Date:** 2026-03-03
**Author:** Kareem Schultz / Alfred
**Status:** Approved
**Scope:** Migration toolkit + upstream PR to mendylivium/mendyfi-radius

---

## Problem

Our fork of mendyfi-radius has diverged significantly from upstream:
- 108 files changed, ~12,000 lines added, ~3,000 removed
- MySQL replaced with PostgreSQL 16
- UI redesigned with Mary UI (Livewire 3 + TailwindCSS + daisyUI)
- Security hardened (www-data, disable_functions, open_basedir)
- Admin panel enhancements, sales report, mobile-first, WCAG compliance

Existing mendyfi users on MySQL need a migration path. The upstream
maintainer (mendylivium) should have the option to adopt these changes.

## Decision

Ship **both**:
1. A standalone migration toolkit that any mendyfi user can run
2. An upstream PR to mendylivium/mendyfi-radius

## Deliverables

### Files in repo

```
migration/
  migrate.sh                    -- Main orchestrator (bash, set -euo pipefail)
  docker-compose.migrate.yml    -- pgloader + temp PostgreSQL 16 containers
  pgloader.load                 -- pgloader configuration with column mappings
  post-migrate.sql              -- PG-specific schema fixes, indexes, sequences
  rollback.sh                   -- Reverses migration, restores MySQL backup
  validate.php                  -- Artisan command: verify row counts + schema
  seed-test-data.sql            -- Test dataset for CI/local testing
  README.md                     -- Step-by-step guide with prerequisites
.github/
  workflows/
    migration-test.yml          -- CI: spin up MySQL, run migration, validate
```

## Migration Flow

### migrate.sh -- Step by step

```
Usage: ./migration/migrate.sh [--dry-run] [--skip-backup] [--mysql-host HOST]

Step 1: Pre-flight checks
  - Docker Engine + Compose v2 present
  - .env file exists and readable
  - MySQL connection succeeds (via docker exec or mysql client)
  - Disk space >= 2x current DB size
  - No other migration in progress (lockfile check)

Step 2: Backup current state
  - mysqldump all tenant databases + central database
  - Copy .env, docker-compose.yml, dockerfiles/ to migration/backups/YYYY-MM-DD/
  - Record MySQL row counts per table for later validation

Step 3: Spin up migration environment
  - docker compose -f migration/docker-compose.migrate.yml up -d
  - Containers: pgloader (dimitri/pgloader), postgresql-16 (postgres:16-alpine)
  - Wait for PostgreSQL to accept connections (pg_isready loop)

Step 4: Create PostgreSQL databases
  - Central database: CREATE DATABASE mendyfi_central
  - Tenant databases: one per tenant from MySQL tenants table
  - Create application user with limited privileges

Step 5: Migrate data with pgloader
  - pgloader reads from MySQL, writes to PostgreSQL
  - pgloader.load handles:
    - AUTO_INCREMENT -> SERIAL/BIGSERIAL
    - TINYINT(1) -> BOOLEAN
    - DATETIME -> TIMESTAMP WITHOUT TIME ZONE
    - ENUM -> VARCHAR with CHECK constraint
    - utf8mb4 -> UTF8
    - Table renames where needed
  - Central database first, then each tenant database
  - Capture pgloader summary output for validation

Step 6: Post-migration schema fixes
  - Run post-migrate.sql:
    - Fix sequence ownership (pgloader doesn't always set this)
    - Add missing indexes from Laravel migrations
    - Rename tables if schema differs (vouchers -> hotspot_vouchers)
    - Set correct search_path for multi-tenant
  - Run: php artisan migrate --force (picks up any new migrations)

Step 7: Update application stack
  - Copy hardened php.root.dockerfile into place
  - Update .env:
    - DB_CONNECTION=pgsql
    - DB_HOST=<new pg container>
    - DB_PORT=5432
    - Remove old MySQL-specific vars
  - Rebuild PHP + scheduler containers
  - Clear Laravel caches (config, route, view, compiled)
  - Fix storage permissions (chown -R www-data:www-data storage/ bootstrap/cache/)

Step 8: Validate
  - Run: php artisan mendyfi:validate-migration
  - Checks:
    - All expected tables exist in PostgreSQL
    - Row counts match MySQL backup counts (within tolerance)
    - Foreign key constraints valid
    - Sample queries succeed (dashboard stats, tenant list, voucher count)
    - Health endpoint returns 200
  - Print PASS/FAIL summary

Step 9: Cleanup
  - Stop pgloader container (no longer needed)
  - Keep PostgreSQL container running (now production)
  - Keep MySQL backup in migration/backups/
  - Print: "Migration complete. MySQL backup at migration/backups/..."
  - Prompt: "Remove old MySQL container? [y/N]"
```

### rollback.sh

```
Step 1: Restore .env from backup
Step 2: Restore docker-compose.yml from backup
Step 3: Point back to MySQL container
Step 4: Rebuild PHP container with original Dockerfile
Step 5: Clear Laravel caches
Step 6: Verify MySQL is accessible
Step 7: Print: "Rolled back to MySQL. PostgreSQL container preserved for debugging."
```

## pgloader.load Configuration

```
LOAD DATABASE
  FROM mysql://{{MYSQL_USER}}:{{MYSQL_PASS}}@{{MYSQL_HOST}}/{{MYSQL_DB}}
  INTO postgresql://{{PG_USER}}:{{PG_PASS}}@{{PG_HOST}}/{{PG_DB}}

WITH
  include drop,
  create tables,
  create indexes,
  reset sequences,
  workers = 4,
  concurrency = 2

SET
  maintenance_work_mem to '128MB',
  work_mem to '64MB'

CAST
  type tinyint to boolean using tinyint-to-boolean,
  type int with extra auto_increment to serial,
  type bigint with extra auto_increment to bigserial,
  type datetime to timestamp

ALTER SCHEMA 'mendyfi_central' RENAME TO 'public'
;
```

## docker-compose.migrate.yml

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

  postgres:
    image: postgres:16-alpine
    environment:
      POSTGRES_USER: mendyfi
      POSTGRES_PASSWORD: ${PG_MIGRATE_PASS:-changeme}
      POSTGRES_DB: mendyfi_central
    volumes:
      - pg-migrate-data:/var/lib/postgresql/data
    networks:
      - migrate-net
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U mendyfi"]
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

## Testing Strategy

### Local test (development/debugging)

1. Spin up a fresh vanilla mendyfi MySQL stack using upstream's docker-compose
2. Seed with test data (migration/seed-test-data.sql): ~5 tenants, ~1000 vouchers
3. Run migrate.sh against it
4. Run validate.php
5. Verify UI loads: dashboard, backups, sales, voucher print
6. Run rollback.sh, verify MySQL stack works again
7. Tear down

### GitHub Actions CI (automated, every push)

```yaml
# .github/workflows/migration-test.yml
name: Migration Test
on: [push, pull_request]
jobs:
  test-migration:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8
        env:
          MYSQL_ROOT_PASSWORD: test
          MYSQL_DATABASE: mendyfi_central
    steps:
      - uses: actions/checkout@v4
      - name: Seed MySQL with test data
        run: mysql -h 127.0.0.1 -u root -ptest < migration/seed-test-data.sql
      - name: Run migration
        run: ./migration/migrate.sh --ci-mode
      - name: Validate migration
        run: docker exec php php artisan mendyfi:validate-migration --strict
      - name: Health check
        run: curl -f http://localhost:8045/health
```

The --ci-mode flag:
- Skips interactive prompts (auto-yes)
- Uses predefined test credentials
- Exits non-zero on any validation failure

### Validation checks (validate.php)

Artisan command: `php artisan mendyfi:validate-migration`

- Compare table list: MySQL backup manifest vs PostgreSQL \dt
- Row count per table: must match within 0 tolerance
- Foreign key integrity: all FK references resolve
- Sequence values: next_val > max(id) for all serial columns
- Sample queries: dashboard stats, tenant list, voucher generate
- Multi-tenant: initialize each tenant, verify DB switches correctly
- Encoding: spot-check UTF8 characters survived migration

## Upstream PR Strategy

**Branch:** `feature/postgresql-mary-ui` off upstream's `master`
**Target:** `mendylivium/mendyfi-radius:master`

**PR title:** "feat: PostgreSQL support, Mary UI redesign, security hardening"

**PR body structure:**
1. Summary (3-5 bullet points)
2. Screenshots: before/after UI comparison
3. Feature comparison table
4. Breaking changes list
5. Migration guide: "Run ./migration/migrate.sh"
6. Offer: "Happy to split into smaller PRs if preferred"
7. Note: "Migration toolkit included -- existing MySQL users can migrate with one command"

**If maintainer wants smaller PRs, split candidates:**
- PR 1: Security hardening only (Dockerfile, nginx config)
- PR 2: PostgreSQL support (schema, migrations, pgloader)
- PR 3: Mary UI redesign (blade templates, tailwind config)
- PR 4: New features (sales report, admin panel, backups)

## Risks and Mitigations

| Risk | Mitigation |
|------|-----------|
| pgloader can't handle mendyfi schema edge cases | Test with real data locally first; pgloader.load has explicit cast rules |
| Multi-tenant DB migration fails mid-way | Script is idempotent; re-run picks up where it left off |
| Upstream maintainer ignores PR | Toolkit works standalone; our fork is the canonical version |
| Users lose data during migration | MySQL backup taken BEFORE any changes; rollback.sh restores |
| CI flakes due to Docker-in-Docker | Use service containers instead of DinD; GitHub Actions supports this natively |

## Out of Scope

- Windows support (Docker Desktop only; untested)
- Migration from versions older than mendyfi-radius 1.x
- Automatic Pangolin/Traefik configuration
- Credential migration to Vault (KareTech-specific)
