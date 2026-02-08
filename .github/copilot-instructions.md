# Copilot Instructions (Kre8iv Clients Platform)

## Build, test, lint
- Install deps: `pnpm install`
- Dev server: `pnpm dev`
- Lint: `pnpm lint`
- Type check: `pnpm type-check`
- Unit tests: `pnpm test`
  - Single test (Vitest): `pnpm test -- path/to/test.test.ts`
- E2E tests: `pnpm test:e2e`
  - Single test (Playwright): `pnpm test:e2e -- path/to/test.spec.ts`
- Production build: `pnpm build`

## High-level architecture
- **Next.js 15 App Router**: UI routes in `app/(auth)` and `app/(dashboard)`; API routes in `app/api/**` with REST-style handlers.
- **Supabase-first backend**: Auth via Supabase; database is PostgreSQL with Row-Level Security (RLS) for multi-tenancy; storage uses Supabase Storage.
- **Drizzle ORM**: Schemas live in `lib/db/schema/**` and drive types; SQL migrations in `lib/db/migrations/**` and seeds in `lib/db/seeds/**`.
- **RBAC + feature flags**: Permissions in `lib/rbac/*`; feature flag system spans `features`, `client_features`, `role_features`, `user_features` with priority User > Role > Client > Global.
- **Background jobs**: Inngest functions in `lib/inngest/functions/**` with API entry at `app/api/inngest`.
- **Integrations**: Stripe (payments/webhooks), Resend (email), Vercel Analytics/Sentry.

## Key conventions
- **Server Components first**: Prefer server components and only opt into client components when interactivity is required.
- **Multi-tenant scoping**: Data is always scoped by `client_id`; rely on RLS in Supabase and enforce permissions via RBAC checks in API routes and UI.
- **API route pattern**: `app/api/[resource]/route.ts` for list/create and `app/api/[resource]/[id]/route.ts` for read/update/delete; nested actions use `[id]/[action]/route.ts`.
- **Validation**: Zod schemas live in `lib/validations/**` and should be used in API routes/forms.
- **UI system**: Base components are in `components/ui/**` (shadcn/ui). Feature components live under `components/<domain>/**`.
- **Feature access**: Use `hasFeatureAccess` from `lib/rbac/check` before showing gated features.
- **Path aliases**: Use `@/` for imports (tsconfig paths).

## Notes from existing AI docs
- The project is a Laravel-to-Next.js migration; implementation is largely complete with environment setup and data migration pending (see `CLAUDE.md`).
