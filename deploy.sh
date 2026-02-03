#!/usr/bin/env bash
set -euo pipefail

# cPanel-friendly deployment script for Laravel
# Run from your project root (the folder that contains artisan).

echo "==\u003e Installing PHP dependencies (production)..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "==\u003e Running migrations..."
php artisan migrate --force

echo "==\u003e Installing Node dependencies..."
npm ci --prefer-offline --no-audit

echo "==\u003e Building production assets..."
npm run build

echo "==\u003e Caching config/routes/views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==\u003e Fixing file permissions..."
# Typical shared-hosting safe defaults:
# - Directories: 755
# - Files: 644
find . -type d -not -path "./vendor/*" -exec chmod 755 {} \;
find . -type f -not -path "./vendor/*" -exec chmod 644 {} \;

echo "==\u003e Ensuring writable directories..."
chmod -R ug+rwx storage bootstrap/cache

echo "==\u003e Done."
