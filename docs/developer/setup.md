# Developer Setup Guide

## Requirements
- PHP 8.2+
- Composer 2.x
- Node.js 18+
- SQLite (for tests) or MySQL (for local dev)

## Local install

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run build
```

## Running tests

```bash
php artisan test
```

## API docs
- Interactive docs (admin-only): `/api/documentation` (alias: `/docs/api`)
- OpenAPI JSON (admin-only): `/api/documentation.json` (alias: `/docs/api.json`)

