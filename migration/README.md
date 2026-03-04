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
