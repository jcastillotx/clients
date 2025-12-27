#!/usr/bin/env bash
set -euo pipefail

# Preflight checks for cPanel deployments.
#
# Usage:
#   APP_ROOT="$HOME/apps/client-portal" ./preflight.sh

APP_ROOT="${APP_ROOT:-$HOME/apps/client-portal}"
CURRENT="${APP_ROOT}/current"
SHARED="${APP_ROOT}/shared"

echo "==> Preflight: ${APP_ROOT}"

if ! command -v php >/dev/null 2>&1; then
  echo "ERROR: php not found in PATH" >&2
  exit 1
fi

php -v | head -n 1 || true

if ! command -v composer >/dev/null 2>&1; then
  echo "ERROR: composer not found in PATH" >&2
  exit 1
fi

if [[ ! -d "${CURRENT}" ]]; then
  echo "WARN: ${CURRENT} does not exist yet (first deploy is OK)."
fi

if [[ ! -f "${SHARED}/.env" ]]; then
  echo "ERROR: missing ${SHARED}/.env (copy .env.production there)" >&2
  exit 1
fi

if [[ -d "${CURRENT}" ]]; then
  if [[ ! -d "${CURRENT}/bootstrap/cache" ]]; then
    echo "ERROR: missing ${CURRENT}/bootstrap/cache" >&2
    exit 1
  fi

  if [[ ! -w "${CURRENT}/bootstrap/cache" ]]; then
    echo "ERROR: ${CURRENT}/bootstrap/cache is not writable" >&2
    exit 1
  fi
fi

echo "==> OK: basic tooling + shared env present"

