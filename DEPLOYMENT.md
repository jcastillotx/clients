# Deployment Instructions

## After pulling latest code from GitHub

Run these commands on your server:

```bash
# 1. Pull latest code
git pull origin main

# 2. Run the deployment script (this will build Tailwind CSS)
bash deploy.sh

# 3. Clear all caches
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 4. Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## If deploy.sh doesn't work

Run these commands manually:

```bash
# Install dependencies
composer install --no-dev --optimize-autoloader

# Install Node packages
npm ci

# Build Tailwind CSS (CRITICAL!)
npm run build

# Run migrations
php artisan migrate --force

# Seed roles if needed
php artisan db:seed --class=RolePermissionSeeder

# Clear and rebuild caches
php artisan view:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Purge Cloudflare Cache

After deployment, **ALWAYS** purge Cloudflare cache:

1. Log into Cloudflare
2. Go to your domain
3. Click "Caching" → "Configuration"
4. Click "Purge Everything"

## Verify Tailwind CSS is loaded

After deployment, check that these files exist:

```bash
ls -lh public/build/assets/app-*.css
ls -lh public/build/assets/app-*.js
```

You should see files like:

- `public/build/assets/app-Bh7UXPZy.css` (around 75KB)
- `public/build/assets/app-Dm92Sxio.js` (around 103KB)

## Troubleshooting

If styles still don't load:

1. Check browser console for 404 errors on CSS files
2. Make sure `public/build` directory exists and has the asset files
3. Clear browser cache (Ctrl+Shift+R or Cmd+Shift+R)
4. Purge Cloudflare cache again
5. Check that Vite manifest exists: `public/build/manifest.json`

## What Changed

- ✅ Removed AdminLTE CSS/JS
- ✅ Removed Bootstrap CSS/JS
- ✅ Removed jQuery
- ✅ Pure Tailwind CSS only
- ✅ Custom brand-tailwind.css for branding
- ✅ Chart.js for dashboard charts

The application now uses **pure Tailwind CSS** with no Bootstrap or AdminLTE dependencies!
