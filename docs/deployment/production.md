# Production Deployment Guide (cPanel + SSH)

This guide is for deploying the Laravel 11 + Livewire client portal to a production cPanel host with SSH/Terminal access.

## Pre-deployment checklist

- [ ] **All environment variables configured** (`.env.production`)
  - [ ] Use `/workspace/.env.production.example` as the baseline
  - [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`
  - [ ] `APP_KEY` generated (`php artisan key:generate --show`)
  - [ ] `DB_*` correct and tested
  - [ ] `CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION` set for your host
  - [ ] `LOG_LEVEL=info` (recommended)
- [ ] **Database migrations tested**
  - [ ] Run `php artisan migrate` on staging with a production-like DB
  - [ ] Confirm any enum/constraint changes are compatible
  - [ ] Confirm rollback plan
- [ ] **Stripe live API keys configured**
  - [ ] `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`
  - [ ] Webhook endpoint configured: `/webhooks/stripe`
  - [ ] Stripe dashboard shows successful deliveries
- [ ] **AWS/Dropbox/Google OAuth credentials (production)**
  - [ ] AWS: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, region, bucket
  - [ ] Dropbox: `DROPBOX_APP_KEY`, `DROPBOX_APP_SECRET`, `DROPBOX_REDIRECT_URI`
  - [ ] Google: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`
- [ ] **SSL certificate installed**
  - [ ] Domain uses HTTPS
  - [ ] HTTP → HTTPS redirect enabled
  - [ ] HSTS enabled *after* validation (optional)
- [ ] **Email SMTP configured and tested**
  - [ ] `MAIL_*` set and verified (send test email)
  - [ ] SPF/DKIM/DMARC set on sending domain (deliverability)
- [ ] **Backup system configured**
  - [ ] Daily DB backups automated
  - [ ] Weekly full backup automated
  - [ ] Retention policy (30 days) configured
  - [ ] Restore procedure tested
- [ ] **Monitoring tools setup**
  - [ ] Uptime monitoring (UptimeRobot)
  - [ ] Error tracking (Sentry, Bugsnag)
  - [ ] APM/performance (New Relic, DataDog) if host supports agents

## cPanel deployment steps (recommended “releases” approach)

### 1) Enable SSH / Terminal in cPanel

- cPanel → **SSH Access** → enable/manage keys
- Ensure your user has **Shell access** (not “Jailed” if you need Supervisor/cron utilities)
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

#### Symlink configuration for public directory (cPanel docroot)
If you *cannot* change the domain docroot:
- Put the Laravel project outside `public_html`
- Symlink `public_html/clients` → `~/apps/client-portal/current/public` (if your cPanel allows symlinks)

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

If you get “permission denied” errors:
- Ensure `bootstrap/cache` exists and is writable
- Ensure your PHP-FPM user/group matches filesystem ownership

### 8) Zero-downtime migration strategy

Best practice:
- Use **expand/contract** migrations (add columns/tables first, deploy code that uses them, then remove old fields later).
- Run migrations **before** switching `current` if code is backwards compatible.

Run:

```bash
php artisan migrate --force
```

Optional (brief maintenance window):
- Put site in maintenance mode (custom page)
- Migrate
- Clear caches
- Bring site back up

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

#### Queue restart after deploy
If you use workers, restart so they pick up new code:

```bash
php artisan queue:restart
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

See also: `scripts/cron/cpanel-cron.txt`

## Database migration strategy notes (production-safe)
- Prefer additive migrations (new columns nullable, new tables, new indexes created online if MySQL supports it).
- Avoid destructive operations during peak hours (drop/rename, enum changes) unless you have a planned window.
- For long-running migrations: schedule a maintenance window and communicate it.

## Post-deployment verification checklist

- [ ] **Client login works**
- [ ] **Admin login works**
- [ ] **Request creation works**
- [ ] **Invoice generation works**
- [ ] **Payment processing works** (do a small real transaction and refund, or use a staging environment for full test-mode coverage)
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

## Documentation deliverables
- Admin user manual: `docs/manuals/admin.md`
- Client user manual: `docs/manuals/client.md`
- API documentation: `/api/documentation` + `docs/developer/api.md`
- Developer setup: `docs/developer/setup.md`
- Troubleshooting: `docs/troubleshooting/guide.md`

## Documentation index

- Admin manual: `docs/manuals/admin.md`
- Client manual: `docs/manuals/client.md`
- API docs: `/api/documentation` in the app + `docs/developer/api.md`
- Developer setup: `docs/developer/setup.md`
- Troubleshooting: `docs/troubleshooting/guide.md`

