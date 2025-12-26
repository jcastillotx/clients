#!/usr/bin/env bash
set -euo pipefail

# cPanel-friendly deployment script for Laravel
# Run from your project root (the folder that contains artisan).

echo "==> Installing PHP dependencies (production)..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Caching config/routes/views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Fixing file permissions..."
# Typical shared-hosting safe defaults:
# - Directories: 755
# - Files: 644
find . -type d -not -path "./vendor/*" -exec chmod 755 {} \;
find . -type f -not -path "./vendor/*" -exec chmod 644 {} \;

echo "==> Ensuring writable directories..."
chmod -R ug+rwx storage bootstrap/cache

echo "==> Done."

