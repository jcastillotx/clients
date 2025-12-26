# Production Deployment Guide (cPanel + SSH)

This guide is for deploying the Laravel 11 + Livewire client portal to a production cPanel host with SSH/Terminal access.

## Pre-deployment checklist

- [ ] **Environment file ready** (`.env.production`)
  - [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`
  - [ ] `APP_KEY` generated
  - [ ] `LOG_CHANNEL` set (recommend `stack`)
  - [ ] `SESSION_DRIVER` and `CACHE_STORE` set appropriately (recommend Redis if available)
  - [ ] `QUEUE_CONNECTION` set (recommend Redis/database + worker)
- [ ] **Database migrations tested**
  - [ ] Run `php artisan migrate` in staging
  - [ ] Validate new features that touch DB
- [ ] **Stripe live keys configured**
  - [ ] `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`
  - [ ] Stripe webhook endpoint configured (see Webhooks section)
- [ ] **AWS/Dropbox/Google OAuth credentials (production)**
  - [ ] AWS keys/region/bucket
  - [ ] Dropbox OAuth app keys + redirect URI
  - [ ] Google OAuth client + redirect URI
- [ ] **SSL certificate installed**
  - [ ] Enforce HTTPS (redirect HTTP → HTTPS)
  - [ ] HSTS enabled (after validation)
- [ ] **Email SMTP configured and tested**
  - [ ] `MAIL_MAILER=smtp` and SMTP creds set
  - [ ] Send a test email from the admin settings panel
- [ ] **Backup system configured**
  - [ ] Daily DB dump automated
  - [ ] Weekly full backup (files + DB)
  - [ ] Retention policy configured (30 days)
- [ ] **Monitoring tools setup**
  - [ ] Error tracking: Sentry/Bugsnag
  - [ ] APM: New Relic/DataDog (optional)
  - [ ] Uptime: UptimeRobot

## cPanel deployment steps (recommended “releases” approach)

### 1) Enable SSH / Terminal in cPanel

- cPanel → **SSH Access** → enable/manage keys
- Confirm you can login:

```bash
ssh username@your-host
```

### 2) Directory layout (symlink-based releases)

Create a structure like:

```
~/apps/client-portal/
  releases/
  shared/
    storage/
    .env
  current -> releases/2025-01-01_120000/
```

The webroot should point to: `~/apps/client-portal/current/public`

On cPanel:
- Preferred: configure the domain document root to `.../current/public`
- Alternative: keep cPanel docroot and place a `public/index.php` forwarder (not recommended)

### 3) Upload code (git or upload)

Option A: git pull in a new release directory

```bash
cd ~/apps/client-portal/releases
mkdir -p "$(date +%F_%H%M%S)" && cd "$_"
git clone <YOUR_REPO_URL> .
```

Option B: upload an archive and extract in the new release folder.

### 4) Install dependencies

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan storage:link || true
```

If Node/Vite build is not available on the server, build locally and upload the compiled assets:
- Ensure `public/build` exists in the release.

### 5) Configure `.env` (shared)

Copy your production env to:

```bash
cp .env.production ~/apps/client-portal/shared/.env
```

Then in the new release folder:

```bash
ln -sfn ~/apps/client-portal/shared/.env .env
```

### 6) Shared storage

Symlink storage to shared:

```bash
rm -rf storage
ln -sfn ~/apps/client-portal/shared/storage storage
mkdir -p ~/apps/client-portal/shared/storage
```

### 7) Permissions

```bash
chmod -R 775 storage bootstrap/cache
```

### 8) Zero-downtime migration strategy

Best practice:
- Use **expand/contract** migrations (add columns/tables first, deploy code that uses them, then remove old fields later).
- Run migrations **before** switching `current` if code is backwards compatible.

Run:

```bash
php artisan migrate --force
```

### 9) Cache warmup (production)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 10) Switch release (atomic)

```bash
ln -sfn ~/apps/client-portal/releases/<NEW_RELEASE> ~/apps/client-portal/current
```

### 11) Queue worker (Supervisor)

If Supervisor is available:
- Copy the sample config from `scripts/supervisor/laravel-worker.conf`
- Use a worker command like:

```bash
php artisan queue:work --sleep=1 --tries=3 --timeout=120
```

If Supervisor is not available on your cPanel plan:
- Use a cron-based worker (less ideal) or upgrade hosting.

### 12) Cron jobs (scheduler)

cPanel → Cron Jobs → add (every minute):

```cron
* * * * * cd /home/USERNAME/apps/client-portal/current && php artisan schedule:run >> /dev/null 2>&1
```

## Post-deployment verification checklist

- [ ] **Client login works**
- [ ] **Admin login works**
- [ ] **Request creation works**
- [ ] **Invoice generation works**
- [ ] **Payment processing works** (Stripe test transaction in production mode is not possible; do a small real transaction and refund)
- [ ] **Storage connections work** (S3/Dropbox/Google Drive)
- [ ] **Email notifications sending**
- [ ] **Webhooks triggering**
- [ ] **File uploads working** (request attachments + documents)

## Monitoring setup

### Queues / Horizon
- If you run Redis and want queue monitoring: install and configure Laravel Horizon.
  - Some cPanel hosts won’t support Redis/Horizon well; in that case monitor workers via Supervisor logs.

### Log monitoring
- Option A: stream server logs via SSH
- Option B: install a log viewer package (ensure it is **admin-only** and protected)

### Uptime monitoring
- UptimeRobot: monitor `/up` health endpoint.

### Error tracking
- Sentry or Bugsnag:
  - Add DSN to env
  - Verify an exception shows up in dashboard

### Performance monitoring
- New Relic/DataDog:
  - Install agent if host supports it
  - Track response times + DB query time

## Backup strategy

### Daily DB backups (automated)
- Use `scripts/backup/db-backup.sh` via cron daily:
  - Keep backups in `~/backups/db`
  - Encrypt if stored off-server

### Weekly full backups
- Include:
  - `shared/storage`
  - `shared/.env` (securely)
  - database dump

### Retention policy (30 days)
- Use `scripts/backup/retention.sh` daily.

### Disaster recovery plan
- Document restore procedure:
  - Restore DB dump to new DB
  - Restore `shared/storage`
  - Repoint domain docroot to new `current/public`

## Security hardening

- **Firewall**: restrict SSH, close unused ports
- **Rate limiting**:
  - API: token-based limiter already exists; tune via settings
  - Login: ensure throttle middleware is enabled
- **Intrusion detection**: fail2ban (if you control the server)
- **Security audits**:
  - Review admin accounts
  - Rotate API tokens
- **Dependency scanning**:
  - Run `composer audit` in CI

## Documentation index

- Admin manual: `docs/manuals/admin.md`
- Client manual: `docs/manuals/client.md`
- API docs: `/api/documentation` in the app + `docs/developer/api.md`
- Developer setup: `docs/developer/setup.md`
- Troubleshooting: `docs/troubleshooting/guide.md`

