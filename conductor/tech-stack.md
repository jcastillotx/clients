# Tech Stack

## Language

- **TypeScript 5.3** — strict mode enabled, no `any` types in production code

## Frontend

- **React 19** — Server Components first
- **Next.js 15.2** — App Router, Server Actions, Edge-compatible
- **Tailwind CSS 3.4** — utility-first styling
- **shadcn/ui** — Radix UI primitives with Tailwind variants
- **Lucide React** — icon library
- **TanStack Query v5** — client-side data fetching and caching
- **React Hook Form + Zod** — form handling with schema validation
- **next-themes** — dark mode support

## Backend

- **Next.js API Routes** — full-stack, co-located with frontend
- **Drizzle ORM 0.29** — type-safe PostgreSQL queries
- **Zod** — runtime validation at all API boundaries

## Database

- **PostgreSQL** (hosted on Supabase)
- **Supabase** — database, auth, storage, realtime
- **Row-Level Security (RLS)** — enforces multi-tenant data isolation at the DB layer
- **Drizzle Kit** — schema migrations (`pnpm db:generate`, `pnpm db:push`)

## Authentication

- **Supabase Auth** — JWT-based, SSR-compatible via `@supabase/ssr`
- JWT contains `user_id`, `client_id`, `role` — used by RLS policies

## Payments

- **Stripe 14** — invoices, subscriptions, webhooks
- **@stripe/react-stripe-js** — frontend Stripe Elements

## Background Jobs

- **Inngest 3** — serverless event-driven jobs (invoice reminders, SLA checks, brand monitoring, analytics sync)
- Endpoint: `app/api/inngest/route.ts`

## Email & Integrations

- **Resend 3** — primary transactional email
- **Microsoft 365 connector** — email integration for agency clients
- **Google Workspace connector** — email/calendar integration for agency clients

## File Storage

- **Supabase Storage** — S3-compatible, client-isolated buckets
- **@better-upload/react** — drag-and-drop upload components

## Testing

- **Vitest** — unit and integration tests
- **Playwright** — E2E tests
- **@playwright/test** — test runner

## Monitoring & Observability

- **Sentry** (`@sentry/nextjs`) — error tracking and performance monitoring
- **Vercel Analytics** — web vitals

## Infrastructure

- **Current**: Vercel (Edge runtime, serverless functions)
- **Planned**: AWS (future migration)
- **Config**: `vercel.json` in project root

## Key Scripts

```bash
pnpm dev          # Start dev server
pnpm build        # Production build
pnpm type-check   # TypeScript check
pnpm lint         # ESLint
pnpm test         # Vitest
pnpm test:e2e     # Playwright
pnpm db:generate  # Generate Drizzle migrations
pnpm db:push      # Push schema to Supabase
pnpm db:studio    # Drizzle Studio UI
```
