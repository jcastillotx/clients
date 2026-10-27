# OpenShip production deployment

The Clients portal runs on self-hosted OpenShip on AWS at https://clients.kre8ivdesigns.com. The application uses a standalone Next.js Docker image, dedicated self-hosted Supabase, Inngest jobs, Microsoft 365 email, and Stripe billing.

## Release

1. Use Node 22 and pnpm 10.30.3. Run type-check, lint, and relevant unit/provider tests.
2. Build `Dockerfile` for Linux amd64 on an off-host builder. Pass only public `NEXT_PUBLIC_*` build arguments. Never copy runtime secrets or staging fixtures into images.
3. Back up the production database before applying new migrations with `ON_ERROR_STOP`.
4. Preserve the active container's environment, networks, aliases, labels, port bindings, resource limits, and proxy settings. Verify a private candidate before promotion.
5. Promote the healthy candidate. Verify `/api/health`, source revision labels, authenticated tenant boundaries, public auth behavior, and relevant provider routes. Keep the prior stopped container and protected configuration snapshots for rollback.
6. Persist runtime configuration in both the protected OpenShip service settings and `/opt/clients-openship/runtime.env`. Confirm managed deployments preserve newly added settings before using automatic deployment.

Production binds `127.0.0.1:20004` to container port 3000 behind the OpenShip HTTPS proxy. Its current limits are 768 MiB and one CPU. Do not compile the application on the production host; an earlier on-host build exhausted available memory.

## Configuration

`DATABASE_URL` points to dedicated PostgreSQL. Authentication and OAuth URLs use the explicit canonical `NEXT_PUBLIC_APP_URL` and `NEXT_PUBLIC_SITE_URL`. Public signup stays disabled; administrators and authorized account managers provision clients. Security response headers are in `config/security-headers.json` and applied by `next.config.mjs`.

Private keys, encryption material, provider refresh tokens, and webhook secrets belong only in protected runtime settings. Stripe platform credentials are distinct from client-owned overrides. Production Stripe events use `/api/webhooks/stripe`; Microsoft email and calendar callbacks use `/api/admin/email/callback/microsoft` and `/api/calendar/callback/microsoft`.

## Rollback and evidence

Revert traffic to the retained previous container if release health fails. Additive migrations do not normally need rollback; never restore a database snapshot over new production writes without reconciliation. Release-specific records are under `docs/qa/`. Historical migration evidence is in `docs/OPENSHIP-MIGRATION.md`.
