#!/usr/bin/env bash
set -euo pipefail

# Weekly “full” backup for release-based deployments.
#
# What it backs up:
# - shared/storage (uploads, generated PDFs, logs)
# - database dump (calls db-backup.sh)
#
# What it does NOT back up by default:
# - application code in releases/ (assume git-based deploys)
# - shared/.env (you may choose to copy it separately and encrypt it)
#
# Usage:
#   APP_ROOT="$HOME/apps/client-portal" BACKUP_DIR="$HOME/backups" ./full-backup.sh
#
# Requires MySQL env vars in your shell for db-backup.sh:
#   DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

APP_ROOT="${APP_ROOT:-$HOME/apps/client-portal}"
BACKUP_DIR="${BACKUP_DIR:-$HOME/backups}"
TS="$(date +%F_%H%M%S)"

mkdir -p "${BACKUP_DIR}/full" "${BACKUP_DIR}/db"

echo "==> Database dump"
BACKUP_DIR="${BACKUP_DIR}/db" "$(dirname "$0")/db-backup.sh"

echo "==> Archiving shared storage"
tar -czf "${BACKUP_DIR}/full/shared_storage_${TS}.tar.gz" -C "${APP_ROOT}" "shared/storage"

echo "==> Done: ${BACKUP_DIR}/full/shared_storage_${TS}.tar.gz"

