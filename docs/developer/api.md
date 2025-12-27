# API Documentation

## In-app interactive docs
- Interactive docs (admin-only): `/api/documentation` (alias: `/docs/api`)
- OpenAPI JSON (admin-only): `/api/documentation.json` (alias: `/docs/api.json`)

## Authentication
- API uses **Laravel Sanctum** personal access tokens.
- Tokens have abilities like: `read`, `write`, `admin`.

## Webhooks
- Configure endpoints in Admin → Webhooks: `/admin/settings/webhooks`
- Deliveries are signed with HMAC SHA-256 via `X-Webhook-Signature`.

