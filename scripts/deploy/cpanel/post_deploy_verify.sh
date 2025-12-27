#!/usr/bin/env bash
set -euo pipefail

# Post-deploy verification helper (server-side).
#
# Usage:
#   APP_ROOT="$HOME/apps/client-portal" ./post_deploy_verify.sh

APP_ROOT="${APP_ROOT:-$HOME/apps/client-portal}"
APP_DIR="${APP_ROOT}/current"

echo "==> Post-deploy verify: ${APP_DIR}"

if [[ ! -d "${APP_DIR}" ]]; then
  echo "ERROR: ${APP_DIR} not found" >&2
  exit 1
fi

cd "${APP_DIR}"

echo "==> Artisan about"
php artisan about || true

echo "==> Migrations status"
php artisan migrate:status || true

echo "==> Cache check"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Queue restart signal"
php artisan queue:restart || true

echo "==> Done"
echo "Next: run the human verification checklist in docs/deployment/production.md (logins, requests, invoices, payments, storage, email, webhooks)."

