# Code Audit Guide

This document describes how to run the repo’s automated checks to catch broken code (syntax errors, failing tests, vulnerable dependencies) and highlights common “red flag” checks to run before shipping.

## Prerequisites
- PHP (matches `composer.json`, currently `^8.2`)
- Composer
- Node.js + npm (for Vite build)

## Install dependencies

```bash
composer install
npm install
```

## Run backend tests

```bash
php artisan test
```

Notes:
- The test suite uses SQLite `:memory:` per `phpunit.xml`.
- If you see warnings about reading `.env`, that usually means a test is trying to read a local `.env` file. The application itself uses `phpunit.xml` env overrides, so tests should not require a committed `.env` file.

## Run dependency vulnerability scan

```bash
composer audit
```

## Run PHP syntax lint across tracked files

```bash
git ls-files "*.php" | xargs -n 50 php -l
```

## Check formatting (Laravel Pint)

```bash
./vendor/bin/pint --test
```

To auto-fix formatting:

```bash
./vendor/bin/pint
```

## Frontend build

```bash
npm run build
```

## Quick “red flag” scans
- Search for unresolved merge conflicts: `<<<<<<<`, `=======`, `>>>>>>>`
- Search for accidental debug calls in app code (examples): `dd(`, `dump(`, `var_dump(`

