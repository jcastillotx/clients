#!/usr/bin/env bash
set -euo pipefail

# cPanel-style release deploy script.
# Assumes:
# - You have a shared directory with .env and storage
# - Your domain docroot points to: $APP_ROOT/current/public
#
# Usage:
#   APP_ROOT="$HOME/apps/client-portal" REPO_URL="git@github.com:org/repo.git" ./deploy_release.sh

APP_ROOT="${APP_ROOT:-$HOME/apps/client-portal}"
REPO_URL="${REPO_URL:-}"
BRANCH="${BRANCH:-main}"

if [[ -z "$REPO_URL" ]]; then
  echo "REPO_URL is required (e.g. git@github.com:org/repo.git)" >&2
  exit 1
fi

RELEASES_DIR="$APP_ROOT/releases"
SHARED_DIR="$APP_ROOT/shared"
TS="$(date +%F_%H%M%S)"
NEW_RELEASE="$RELEASES_DIR/$TS"

mkdir -p "$RELEASES_DIR" "$SHARED_DIR/storage"

echo "==> Creating release: $NEW_RELEASE"
mkdir -p "$NEW_RELEASE"
git clone --branch "$BRANCH" --depth 1 "$REPO_URL" "$NEW_RELEASE"

echo "==> Linking shared .env and storage"
if [[ ! -f "$SHARED_DIR/.env" ]]; then
  echo "Missing $SHARED_DIR/.env. Copy your production env there first." >&2
  exit 1
fi
ln -sfn "$SHARED_DIR/.env" "$NEW_RELEASE/.env"
rm -rf "$NEW_RELEASE/storage"
ln -sfn "$SHARED_DIR/storage" "$NEW_RELEASE/storage"

echo "==> Installing dependencies"
cd "$NEW_RELEASE"
composer install --no-dev --prefer-dist --optimize-autoloader

echo "==> Permissions"
chmod -R 775 "$NEW_RELEASE/storage" "$NEW_RELEASE/bootstrap/cache" || true

echo "==> Running migrations"
php artisan migrate --force

echo "==> Caching"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Switching current symlink"
ln -sfn "$NEW_RELEASE" "$APP_ROOT/current"

echo "==> Signaling workers"
php artisan queue:restart || true

echo "==> Done. Current release is now: $TS"
echo "Tip: restart queue workers if needed (Supervisor)."

