#!/usr/bin/env bash
set -euo pipefail

# rollback.sh -- Reverses PostgreSQL migration, restores MySQL state.
# Usage: ./migration/rollback.sh [--backup-dir DIR]

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR=""

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
