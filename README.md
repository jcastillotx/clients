# Kre8iv Designs Client Portal

A self-service client management portal built with Laravel 11, Livewire 3, and AdminLTE. This portal enables clients to view and manage service requests, contracts, invoices, and documents.

## Features

- **Client Authentication & Dashboard** - Secure login with role-based access control
- **Service Request Management** - Create, view, edit, and comment on service requests
- **Contract Management** - View contracts with e-signature capability
- **Invoice Display & Payments** - View invoices with Stripe payment integration
- **Document Library** - Upload and download documents
- **Activity Logging** - Complete audit trail for all actions
- **Marketing Toolkit (MVP scaffolding)** - Website audits, SEO/brand/content/campaign/reputation data models + scheduled runners (feature flags via config/env)

## Tech Stack

- **Framework**: Laravel 11
- **Frontend**: Livewire 3 + Tailwind CSS + AdminLTE 3
- **Database**: MySQL
- **Authentication**: Laravel Breeze
- **Authorization**: Spatie Laravel Permission
- **Payments**: Stripe PHP SDK
- **PDF Generation**: DomPDF
- **HTTP clients**: Laravel HTTP Client + Guzzle (provider integrations)

## Requirements

- PHP 8.2+
- Composer 2.x
- Node.js 18+ & NPM
- MySQL 8.0+

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/your-repo/client-portal.git
cd client-portal
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node Dependencies

```bash
npm install
```

### 4. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure:
- Database credentials (`DB_*`)
- Mail settings (`MAIL_*`)
- Stripe keys (`STRIPE_*`)

### 5. Database Setup

```bash
php artisan migrate
php artisan db:seed
```

### 6. Build Assets

```bash
npm run build
```

### 7. Storage Link

```bash
php artisan storage:link
```

### 8. Start Development Server

```bash
php artisan serve
```

Visit `http://localhost:8000`

## Default Login Credentials

After running seeders:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@kre8ivdesigns.com | password |
| Staff | staff@kre8ivdesigns.com | password |
| Client | client@demo.com | password |

## Directory Structure

```
├── app/
│   ├── Http/Controllers/    # HTTP controllers
│   ├── Livewire/           # Livewire components
│   ├── Models/             # Eloquent models
│   ├── Providers/          # Service providers
│   └── View/Components/    # Blade components
├── config/
│   ├── client-portal.php   # Portal-specific config
│   └── ...
├── database/
│   ├── migrations/         # Database migrations
│   └── seeders/           # Database seeders
├── resources/
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript
│   └── views/             # Blade templates
├── routes/
│   ├── web.php            # Web routes
│   └── auth.php           # Auth routes
└── storage/
    └── app/
        ├── documents/     # Client documents
        ├── contracts/     # Contract files
        ├── invoices/      # Generated invoices
        └── attachments/   # Request attachments
```

## Deployment to cPanel

### 1. Upload Files

Upload all files to your cPanel account via File Manager or FTP.

### 2. Point Domain to Public Directory

Configure `clients.kre8ivdesigns.com` to point to the `public/` directory.

### 3. Configure PHP Version

Ensure PHP 8.2+ is selected in cPanel's MultiPHP Manager.

### 4. Environment Variables

Create/edit `.env` file with production settings:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://clients.kre8ivdesigns.com
```

### 5. Run Migrations

Via SSH or cPanel Terminal:

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6. Set Permissions

```bash
chmod -R 755 storage bootstrap/cache
```

### 7. Setup Cron Job

Add to cPanel Cron Jobs (run every minute):

```
* * * * * cd /home/username/public_html/clients && php artisan schedule:run >> /dev/null 2>&1
```

### Production deployment guide + scripts
- Guide: `docs/deployment/production.md`
- Example production env: `.env.production.example`
- Scripts:
  - cPanel releases deploy: `scripts/deploy/cpanel/deploy_release.sh`
  - Preflight checks: `scripts/deploy/cpanel/preflight.sh`
  - Post-deploy verify: `scripts/deploy/cpanel/post_deploy_verify.sh`
  - Backups: `scripts/backup/`
  - Cron templates: `scripts/cron/cpanel-cron.txt`

## Stripe Configuration

1. Create a Stripe account at https://stripe.com
2. Get API keys from Dashboard → Developers → API keys
3. Add to `.env`:
   ```
   STRIPE_KEY=pk_live_xxx
   STRIPE_SECRET=sk_live_xxx
   STRIPE_WEBHOOK_SECRET=whsec_xxx
   ```
4. Set up webhook endpoint: `https://clients.kre8ivdesigns.com/webhooks/stripe`
5. Subscribe to events:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `charge.refunded`

## Customization

### Adding Request Types

Edit `config/client-portal.php`:

```php
'request_types' => [
    'web_development' => 'Web Development',
    'your_new_type' => 'Your New Type',
    // ...
],
```

### Modifying Invoice Template

Edit `resources/views/invoices/pdf.blade.php` to customize the PDF invoice template.

### Changing Branding

1. Update logo: `public/images/logo.png`
2. Edit colors in `resources/css/app.css`
3. Rebuild: `npm run build`

## Security

- All client data is scoped to their account
- CSRF protection on all forms
- Password hashing with bcrypt
- Rate limiting on authentication routes
- SQL injection protection via Eloquent ORM
- XSS protection via Blade templating

## Maintenance

### Clear Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Check Overdue Invoices

Runs automatically via scheduler, or manually:

```bash
php artisan schedule:run
```

## Marketing Toolkit (Audits, SEO, Brand, Content, Campaigns)

This repo includes a **Marketing module** (under `app/Services/Marketing/`) that provides:

- **Website auditing**: crawl + SEO/performance/security/mobile/accessibility checks with optional AI recommendations.
- **SEO monitoring scaffolding**: keyword tracking tables, rankings history, backlink tables, recommendation tracking.
- **Brand audit + brand guide scaffolding**: brand audits, assets, competitors, and digital brand guide data structures.
- **Content planning scaffolding**: content calendar, themes, templates, and social account connections.
- **Campaign management scaffolding**: campaigns, links/UTMs, assets, metrics history.
- **Unified analytics scaffolding**: normalized `marketing_metrics` plus dashboard + scheduled report tables.
- **Leads, assets, reviews scaffolding**: leads + nurture sequences, centralized asset rows, review storage.

### Scheduler / Queue requirements

Marketing workflows rely on:

- **Laravel scheduler** (`php artisan schedule:run`) for periodic runners (every 5 minutes).
- **Queue worker** for long-running audits and external API calls.

The scheduler is already wired in `routes/console.php` for:
- `send-scheduled-admin-reports`
- `run-scheduled-website-audits`

### Key environment variables

Website auditing + integrations (see `config/website-auditor.php`):

```env
# Crawl behavior
WEBSITE_AUDIT_MAX_PAGES=50
WEBSITE_AUDIT_RESPECT_ROBOTS=true
WEBSITE_AUDIT_MAX_LINK_CHECKS=200

# Google PageSpeed Insights (optional)
GOOGLE_PAGESPEED_API_KEY=

# Provider placeholders (optional)
WEBPAGETEST_API_KEY=
GTMETRIX_EMAIL=
GTMETRIX_API_KEY=
AHREFS_API_KEY=
SEMRUSH_API_KEY=
MOZ_ACCESS_ID=
MOZ_SECRET_KEY=
```

### Admin UI (Website Auditor MVP)

If you have `permission:access admin panel`:
- Website auditor: `/admin/marketing/website-auditor`
- Audit results: `/admin/marketing/audit-results`
- PDF export: available per completed audit in results table

## Support

For support, email support@kre8ivdesigns.com

## License

Proprietary - Kre8iv Designs LLC
