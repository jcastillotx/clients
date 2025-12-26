#!/usr/bin/env bash
set -euo pipefail

# Generic retention pruning helper.
#
# Usage:
#   RETENTION_DAYS=30 TARGET_DIR="$HOME/backups" ./retention.sh

TARGET_DIR="${TARGET_DIR:-$HOME/backups}"
RETENTION_DAYS="${RETENTION_DAYS:-30}"

echo "Pruning files older than ${RETENTION_DAYS} days in ${TARGET_DIR}"
find "$TARGET_DIR" -type f -mtime "+$RETENTION_DAYS" -delete
echo "Done."

