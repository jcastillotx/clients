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
- Interactive docs: `/api/documentation` (admin-only in app)
- Scramble UI: `/docs/api`
- OpenAPI JSON: `/docs/api.json`

