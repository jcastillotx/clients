# Laravel Server Update Guide

This guide explains how to safely update your Laravel application on your production server.

---

## Current Application Info

- **Laravel Version**: 11.x
- **PHP Requirement**: ^8.2
- **Framework**: Laravel 11 with Livewire 3

---

## Pre-Update Checklist

Before updating Laravel on your server, ensure you have:

- [ ] **Full database backup** (critical!)
- [ ] **Code backup** (Git commit or server snapshot)
- [ ] **Maintenance mode plan** (to prevent user access during update)
- [ ] **SSH access** to your server
- [ ] **Composer installed** on the server
- [ ] **Tested updates locally** first

---

## Method 1: Manual Update via SSH (Recommended)

### Step 1: Connect to Your Server

```bash
ssh user@your-server-ip
cd /path/to/your/laravel/app
```

### Step 2: Enable Maintenance Mode

```bash
php artisan down --message="Updating application. We'll be back shortly!" --retry=60
```

This displays a maintenance page to users and tells them to retry in 60 seconds.

### Step 3: Backup Your Database

```bash
# For MySQL
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Or use Laravel backup if you have it configured
php artisan backup:run
```

### Step 4: Pull Latest Code from Git

```bash
# Stash any local changes (if needed)
git stash

# Pull latest code
git pull origin main

# Or checkout a specific tag/version
git fetch --tags
git checkout v1.0.0
```

### Step 5: Update Dependencies

```bash
# Update Composer dependencies
composer install --no-dev --optimize-autoloader

# Clear and rebuild cache
composer dump-autoload --optimize
```

### Step 6: Run Migrations

```bash
# Run database migrations
php artisan migrate --force

# The --force flag is required in production
```

### Step 7: Clear All Caches

```bash
# Clear application cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Clear view cache
php artisan view:clear

# Rebuild optimized caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 8: Update NPM Assets (if needed)

```bash
# Install Node dependencies
npm ci

# Build production assets
npm run build
```

### Step 9: Set Correct Permissions

```bash
# Set ownership (adjust user/group as needed)
sudo chown -R www-data:www-data storage bootstrap/cache

# Set permissions
sudo chmod -R 775 storage bootstrap/cache
```

### Step 10: Disable Maintenance Mode

```bash
php artisan up
```

### Step 11: Verify the Update

```bash
# Check Laravel version
php artisan --version

# Run a quick health check
php artisan route:list
php artisan config:show app
```

---

## Method 2: Using GitHub Actions CI/CD (Automated)

If you have the CI/CD pipeline set up (`.github/workflows/deploy.yml`):

### Step 1: Push Code to GitHub

```bash
# On your local machine
git add .
git commit -m "Update Laravel application"
git push origin main
```

### Step 2: Monitor Deployment

1. Go to your GitHub repository
2. Click **Actions** tab
3. Watch the deployment workflow run
4. Verify it completes successfully

The CI/CD pipeline will automatically:

- Run tests
- Install dependencies
- Deploy to your server
- Run migrations
- Clear caches
- Restart services

---

## Method 3: Using Deployment Tools

### Using Laravel Forge

1. Log in to [Laravel Forge](https://forge.laravel.com)
2. Select your server and site
3. Click **Deployment** tab
4. Click **Deploy Now**
5. Monitor the deployment log

### Using Envoyer

1. Log in to [Envoyer](https://envoyer.io)
2. Select your project
3. Click **Deploy**
4. Monitor the deployment progress

---

## Updating Specific Components

### Update Laravel Framework Only

```bash
# Update to latest Laravel 11.x
composer update laravel/framework --with-dependencies

# Or update to a specific version
composer require laravel/framework:^11.0
```

### Update All Dependencies

```bash
# Update all packages to latest compatible versions
composer update

# Or update with production optimizations
composer update --no-dev --optimize-autoloader
```

### Update Livewire

```bash
composer update livewire/livewire
php artisan livewire:publish --config
php artisan livewire:publish --assets
```

---

## Troubleshooting Common Issues

### Issue: "Class not found" errors

**Solution:**

```bash
composer dump-autoload --optimize
php artisan clear-compiled
php artisan cache:clear
```

### Issue: "Permission denied" errors

**Solution:**

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Issue: Database migration errors

**Solution:**

```bash
# Check migration status
php artisan migrate:status

# Rollback last migration
php artisan migrate:rollback --step=1

# Re-run migrations
php artisan migrate --force
```

### Issue: 500 Internal Server Error

**Solution:**

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check web server logs
sudo tail -f /var/log/nginx/error.log  # Nginx
sudo tail -f /var/log/apache2/error.log  # Apache

# Enable debug mode temporarily (NEVER in production long-term!)
# Edit .env: APP_DEBUG=true
php artisan config:clear
```

### Issue: Assets not loading

**Solution:**

```bash
# Rebuild assets
npm run build

# Clear browser cache
# Or add cache-busting query string to asset URLs
```

---

## Post-Update Verification

### 1. Check Application Health

```bash
# Verify artisan commands work
php artisan --version
php artisan route:list

# Check queue workers (if using)
php artisan queue:restart
```

### 2. Test Critical Features

- [ ] Login/authentication works
- [ ] Database connections work
- [ ] File uploads work
- [ ] Email sending works
- [ ] API endpoints respond
- [ ] Scheduled tasks run

### 3. Monitor Error Logs

```bash
# Watch Laravel logs
tail -f storage/logs/laravel.log

# Watch web server logs
sudo tail -f /var/log/nginx/error.log
```

### 4. Check Performance

```bash
# Verify caches are built
ls -la bootstrap/cache/
ls -la storage/framework/cache/

# Check opcache status (if using PHP-FPM)
php -i | grep opcache
```

---

## Rollback Procedure (If Something Goes Wrong)

### Quick Rollback

```bash
# Enable maintenance mode
php artisan down

# Restore database backup
mysql -u username -p database_name < backup_20260202_123456.sql

# Checkout previous Git commit
git log --oneline -5  # Find the previous commit hash
git checkout abc123  # Replace with actual commit hash

# Reinstall dependencies
composer install --no-dev --optimize-autoloader

# Run migrations (if needed)
php artisan migrate --force

# Clear caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# Disable maintenance mode
php artisan up
```

---

## Best Practices

1. **Always test updates locally first**
   - Use a staging environment
   - Test all critical features
   - Check for deprecation warnings

2. **Update during low-traffic periods**
   - Schedule updates during off-peak hours
   - Notify users in advance

3. **Keep backups**
   - Automated daily database backups
   - Git version control for code
   - Server snapshots before major updates

4. **Use semantic versioning**
   - Tag releases in Git
   - Document changes in CHANGELOG.md
   - Follow Laravel's upgrade guides

5. **Monitor after deployment**
   - Watch error logs for 24 hours
   - Monitor performance metrics
   - Have rollback plan ready

---

## Automated Update Script

Create a deployment script for consistent updates:

```bash
#!/bin/bash
# deploy.sh - Laravel deployment script

set -e  # Exit on error

echo "🚀 Starting deployment..."

# Enable maintenance mode
php artisan down

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build assets
npm ci
npm run build

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Restart queue workers
php artisan queue:restart

# Disable maintenance mode
php artisan up

echo "✅ Deployment complete!"
```

Make it executable:

```bash
chmod +x deploy.sh
```

Run it:

```bash
./deploy.sh
```

---

## Server-Specific Considerations

### Shared Hosting

- Use cPanel or hosting control panel
- May need to use `php artisan` via SSH or cron jobs
- Limited access to server configuration
- Contact hosting support for PHP/Composer updates

### VPS/Dedicated Server

- Full control over server configuration
- Can use process managers (Supervisor for queues)
- Can configure web server (Nginx/Apache)
- Responsible for server security updates

### Cloud Platforms (AWS, DigitalOcean, etc.)

- Use deployment tools (CodeDeploy, GitHub Actions)
- Consider using load balancers for zero-downtime
- Utilize managed databases for easier backups
- Use CDN for static assets

---

## Additional Resources

- [Laravel Upgrade Guide](https://laravel.com/docs/11.x/upgrade)
- [Laravel Deployment Documentation](https://laravel.com/docs/11.x/deployment)
- [Composer Documentation](https://getcomposer.org/doc/)
- [Laravel Forge](https://forge.laravel.com) - Server management
- [Envoyer](https://envoyer.io) - Zero-downtime deployment

---

## Need Help?

If you encounter issues during the update:

1. Check the Laravel logs: `storage/logs/laravel.log`
2. Review the web server error logs
3. Consult the [Laravel Discord](https://discord.gg/laravel)
4. Search [Laravel Forums](https://laracasts.com/discuss)
5. Check [Stack Overflow](https://stackoverflow.com/questions/tagged/laravel)

**Remember**: Always have a backup and rollback plan before updating production!
