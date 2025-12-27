# Technical Schematic (Architecture, Functions, Capabilities)

This document is the developer-facing blueprint for how the Client Portal is built: the layers, modules, data model, integrations, and operational wiring (queue/scheduler/webhooks).

## High-level architecture

**Framework pattern**
- **Laravel 11** MVC for standard HTTP pages + REST-ish endpoints.
- **Livewire 3** for interactive/admin and “app-like” pages (most of the portal UI).
- **Blade views** for page shells and PDF templates.

**Primary layers**
- **Routes**: `routes/web.php`, `routes/auth.php`, `routes/api.php`, `routes/console.php`
- **Controllers**: `app/Http/Controllers/**` (web pages + API)
- **Livewire components**: `app/Http/Livewire/**` (most feature UIs)
- **Domain models**: `app/Models/**` (Eloquent + relationships + domain helpers)
- **Services**: `app/Services/**` (integration logic + business workflows)
- **Jobs**: `app/Jobs/**` (async processing, scheduled work, external calls)
- **Observers**: `app/Observers/**` (event-driven automation/webhooks/AI triggers)
- **Config**: `config/*.php` (portal settings, storage providers, AI providers, etc.)

## Runtime “wiring” (boot, scheduler, queues)

### App boot hooks
`app/Providers/AppServiceProvider.php`:
- **Registers**: `AIProviderManager` as a singleton.
- **Rate limits**: `api-token` limiter (60 req/min per token).
- **Attaches observers** to models (Request/Invoice/Contract/Document/DocumentShare/Payment).
- **Adds Blade directives**: `@money(...)`, `@status(...)`.

### Scheduler (cron)
`routes/console.php` wires scheduled tasks:
- Invoice maintenance: mark “sent” invoices overdue after due date.
- Automation triggers: `schedule.daily|weekly|monthly`, invoice due approaching, storage quota reached, contract expiring.
- Scheduled reports runner (every 5 minutes).
- Website audit schedule runner (every 5 minutes).
- Storage sync scheduler (every 5 minutes).
- AI analytics jobs (daily/weekly/monthly/quarterly).
- Utility command: request embeddings backfill (`ai:embeddings:backfill`).

### Queue workloads
