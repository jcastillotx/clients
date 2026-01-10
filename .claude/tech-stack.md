# Technical Stack & Architecture

## Core Technology Stack

### Backend Framework
- **Laravel 11.x** - PHP framework (MVC pattern)
- **PHP 8.2+** - Programming language
- **Composer 2.x** - Dependency management

### Frontend Stack
- **Livewire 3.x** - Full-stack framework for interactive UIs
- **Tailwind CSS 3.x** - Utility-first CSS framework
- **AdminLTE 3** - Admin dashboard template
- **Alpine.js** (via Livewire) - Minimal JavaScript framework
- **Vite** - Frontend build tool

### Database
- **MySQL 8.0+** - Primary database
- **SQLite** - Testing database (in-memory)

### Authentication & Authorization
- **Laravel Breeze** - Authentication scaffolding
- **Spatie Laravel Permission** - Roles and permissions management
- **Laravel Sanctum** - API token authentication

## Architecture Patterns

### Application Layer Structure

```
┌─────────────────────────────────────────┐
│         Routes (web.php, api.php)       │
└──────────────────┬──────────────────────┘
                   │
     ┌─────────────┴─────────────┐
     │                           │
┌────▼─────┐            ┌────────▼────────┐
│Controllers│            │Livewire Components│
│ (HTTP)   │            │   (Interactive) │
└────┬─────┘            └────────┬────────┘
     │                           │
     └─────────────┬─────────────┘
                   │
            ┌──────▼──────┐
            │  Services   │
            │ (Business)  │
            └──────┬──────┘
                   │
     ┌─────────────┼─────────────┐
     │             │             │
┌────▼────┐  ┌────▼────┐  ┌─────▼─────┐
│ Models  │  │  Jobs   │  │Integrations│
│(Eloquent)│  │ (Queue) │  │  (APIs)   │
└────┬────┘  └────┬────┘  └─────┬─────┘
     │            │              │
     └────────────┴──────────────┘
                  │
          ┌───────▼────────┐
          │    Database    │
          └────────────────┘
```

### Primary Layers

#### 1. Routes Layer
- **Location**: `routes/`
- `web.php` - Web application routes
- `auth.php` - Authentication routes (Breeze)
- `api.php` - API endpoints
- `console.php` - Scheduled tasks and commands

#### 2. Controller Layer
- **Location**: `app/Http/Controllers/`
- Traditional HTTP controllers for simple CRUD
- OAuth controllers for external integrations
- Webhook receivers (Stripe, storage providers)
- API controllers for external access

#### 3. Livewire Component Layer
- **Location**: `app/Http/Livewire/`
- **Views**: `resources/views/livewire/`
- Interactive UI components (most of the application)
- Real-time updates without page refresh
- Component lifecycle: mount → render → actions → updated
- Event-driven communication between components

#### 4. Service Layer
- **Location**: `app/Services/`
- Business logic encapsulation
- External API integrations
- Complex workflows
- Examples:
  - `AI/` - AI provider management
  - `Social/` - Social media integrations
  - `Storage/` - Cloud storage providers
  - `Marketing/` - Website audits, brand monitoring
  - `Estimates/` - Project estimation logic

#### 5. Model Layer
- **Location**: `app/Models/`
- Eloquent ORM models
- Database relationships
- Query scopes
- Model observers for automation
- Key models:
  - `User` - System users (clients, staff, admin)
  - `Client` - Client organizations
  - `Request` - Service requests
  - `Invoice` - Billing
  - `Contract` - Agreements
  - `Document` - File management

#### 6. Job Layer
- **Location**: `app/Jobs/`
- Asynchronous processing
- Queue workers execute jobs
- Examples:
  - API calls to external services
  - Email sending
  - Document processing
  - Brand monitoring collection
  - Storage synchronization

#### 7. Observer Layer
- **Location**: `app/Observers/`
- Event-driven automation
- Registered in `AppServiceProvider`
- Triggers: created, updated, deleted events
- Examples:
  - Activity logging
  - Notifications
  - AI workflow triggers
  - Webhook dispatching

## Database Architecture

### Schema Organization

#### Core Tables
- `users` - System users (polymorphic with staff/clients)
- `clients` - Client organizations
- `client_user` - Pivot table for client-user relationships

#### Feature Tables
- `requests` - Service requests
- `request_comments` - Comments on requests
- `invoices` + `invoice_items` - Billing
- `contracts` - Client agreements
- `documents` - File storage metadata
- `payments` - Payment transactions

#### Integration Tables
- `storage_connections` - S3/Dropbox/Drive connections
- `social_accounts` - Social media OAuth connections
- `social_posts` - Content calendar
- `brand_mentions` - Brand monitoring data

#### AI Tables
- `ai_providers` - AI service configurations
- `ai_usage_logs` - Cost tracking
- `ai_safety_logs` - Compliance logging
- `ai_assistants` - Custom assistant configurations

#### Activity & Analytics
- `activity_log` (Spatie) - Audit trail
- `marketing_metrics` - Unified analytics
- `website_audits` + `audit_results` - Website monitoring

### Relationship Patterns

#### Client Data Scoping
Most tables have `client_id` foreign key for multi-tenancy:
```php
// Auto-scoped in queries
Request::where('client_id', auth()->user()->client_id)->get();

// Global scopes on models
protected static function booted()
{
    static::addGlobalScope('client', function (Builder $query) {
        if (auth()->user()?->client_id) {
            $query->where('client_id', auth()->user()->client_id);
        }
    });
}
```

#### User-Client Polymorphism
- Users can be staff (no client_id) or clients (with client_id)
- Permissions checked via Spatie roles
- Data access controlled by client_id scope

## Key Dependencies

### PHP Packages (composer.json)

#### Core Framework
```json
{
  "laravel/framework": "^11.0",
  "livewire/livewire": "^3.4"
}
```

#### Authentication & Authorization
```json
{
  "laravel/breeze": "^2.0",
  "laravel/sanctum": "^4.0",
  "spatie/laravel-permission": "^6.4"
}
```

#### Integrations
```json
{
  "stripe/stripe-php": "^13.0",
  "aws/aws-sdk-php": "^3.369",
  "spatie/dropbox-api": "^1.23",
  "google/apiclient": "^2.18",
  "openai-php/client": "^0.10"
}
```

#### Utilities
```json
{
  "barryvdh/laravel-dompdf": "^3.0",
  "maatwebsite/excel": "^3.1",
  "spatie/laravel-activitylog": "^4.0",
  "minishlink/web-push": "^9.0"
}
```

### Frontend Dependencies (package.json)

```json
{
  "tailwindcss": "^3.x",
  "alpinejs": "^3.x",
  "vite": "^5.x"
}
```

## Runtime Components

### Scheduler (Cron)
- **Location**: `routes/console.php`
- **Frequency**: Every minute (cron: `* * * * *`)
- **Tasks**:
  - Invoice overdue checks
  - Scheduled reports
  - Website audit scheduler
  - Storage sync
  - AI analytics jobs
  - Brand monitoring collection

### Queue Workers
- **Driver**: Database (configurable to Redis/SQS)
- **Usage**: Long-running API calls, emails, processing
- **Command**: `php artisan queue:work`
- **Deployment**: Should run as daemon (supervisor/systemd)

### Webhooks
- **Stripe**: `/webhooks/stripe` - Payment events
- **Storage**: Provider-specific endpoints for sync notifications

### Cache
- **Driver**: File (configurable to Redis/Memcached)
- **Usage**: Config cache, route cache, view cache
- **Commands**:
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```

## Configuration System

### Environment-Driven Config
- Primary: `.env` file
- Examples: `.env.example`, `.env.production.example`
- Auto-loaded into `config/*.php` files

### Key Config Files

#### `config/client-portal.php`
- Request types, statuses, priorities
- Invoice settings and branding
- File upload limits
- Support email

#### `config/features.php`
- Feature gating per client tier
- Enable/disable capabilities

#### `config/ai-providers.php`
- AI service configurations
- Model selections
- Rate limits

#### `config/services.php`
- OAuth credentials (social, storage)
- External API keys

#### `config/branding.php`
- White-label branding settings
- Company info, colors, logos

## Performance Optimizations

### Database
- Eager loading relationships (`with()`)
- Proper indexing on foreign keys
- Query scopes for common filters

### Caching
- Config/route/view caching in production
- Query result caching for expensive operations
- Browser caching for assets

### Queue Processing
- Defer heavy operations to jobs
- Batch processing for bulk operations
- Failed job retry logic

### Asset Optimization
- Vite for bundling and minification
- Lazy loading for heavy components
- CDN-ready asset URLs

## Security Architecture

### Authentication
- Bcrypt password hashing
- Session-based auth (Breeze)
- Remember me tokens
- 2FA support (optional)

### Authorization
- Policy-based (Laravel Policies)
- Role-based (Spatie Permissions)
- Row-level security (client_id scoping)

### Input Validation
- Form Request validation
- Livewire property validation
- File upload type/size checks

### Output Protection
- Blade auto-escaping (XSS prevention)
- CSRF tokens (automatic)
- SQL injection prevention (Eloquent)

### API Security
- Sanctum token authentication
- Rate limiting (60 req/min default)
- CORS configuration

## Development Tools

### Code Quality
- **Laravel Pint** - PHP code style fixer (PSR-12)
- **PHPUnit** - Testing framework
- **Laravel Dusk** - Browser testing

### Debugging
- **Laravel Telescope** - Application insights (dev only)
- **Laravel Debugbar** - Debug toolbar (dev only)
- **Log files** - `storage/logs/laravel.log`

### API Documentation
- **Scramble** - Auto-generated API docs
