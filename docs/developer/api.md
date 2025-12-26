# API Documentation

## In-app interactive docs
- Admin-only wrapper: `/api/documentation`
- Scramble UI: `/docs/api`
- OpenAPI JSON: `/docs/api.json`

## Authentication
- API uses **Laravel Sanctum** personal access tokens.
- Tokens have abilities like: `read`, `write`, `admin`.

## Webhooks
- Configure endpoints in Admin → Webhooks: `/admin/settings/webhooks`
- Deliveries are signed with HMAC SHA-256 via `X-Webhook-Signature`.

