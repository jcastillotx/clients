#!/usr/bin/env bash
set -euo pipefail

# Simple database backup script for MySQL.
# Requires env vars:
#   DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
#
# Usage:
#   source /home/USERNAME/apps/client-portal/shared/.env && ./db-backup.sh

BACKUP_DIR="${BACKUP_DIR:-$HOME/backups/db}"
RETENTION_DAYS="${RETENTION_DAYS:-30}"

mkdir -p "$BACKUP_DIR"

TS="$(date +%F_%H%M%S)"
OUT="$BACKUP_DIR/${DB_DATABASE}_${TS}.sql.gz"

echo "Backing up $DB_DATABASE to $OUT"

mysqldump \
  --host="${DB_HOST:-127.0.0.1}" \
  --port="${DB_PORT:-3306}" \
  --user="${DB_USERNAME:?DB_USERNAME required}" \
  --password="${DB_PASSWORD:?DB_PASSWORD required}" \
  --single-transaction \
  --quick \
  --routines \
  --events \
  "${DB_DATABASE:?DB_DATABASE required}" \
  | gzip -9 > "$OUT"

echo "Pruning backups older than ${RETENTION_DAYS} days"
find "$BACKUP_DIR" -type f -name "*.sql.gz" -mtime "+$RETENTION_DAYS" -delete

echo "Done."

