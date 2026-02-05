# Technology Stack - Laravel to Next.js Migration

## Frontend

### Core Framework

- **Next.js 15** (App Router)
  - Why: Server Components reduce JS bundle, better SEO, built-in optimizations
  - Features: App Router, Server Components, Server Actions, Image optimization

- **TypeScript 5.3+**
  - Why: Type safety prevents runtime errors, better DX with autocomplete
  - Config: Strict mode enabled

### Styling

- **Tailwind CSS 3.4**
  - Why: Utility-first, consistent design system, no conflicts (single framework)
  - Config: Custom brand colors, responsive breakpoints, dark mode support

### UI Components

- **shadcn/ui** (Radix UI + Tailwind)
  - Why: Copy-paste components (no NPM bloat), accessible by default, customizable
  - Components: Button, Card, Dialog, Form, Input, Select, Table, Toast, Sheet
  - Foundation: Radix UI primitives ensure WCAG 2.1 AA compliance

### Forms & Validation

- **React Hook Form**
  - Why: Performant (uncontrolled components), minimal re-renders
  - Features: Built-in validation, easy error handling

- **Zod**
  - Why: TypeScript-first schema validation, inference for types
  - Usage: API validation, form validation, runtime type checking

### State Management

- **Server Components** (default)
  - Why: No client-side state needed for most data fetching

- **TanStack Query v5**
  - Why: Client-side caching, automatic refetching, optimistic updates
  - Features: Infinite queries, mutation handling, devtools

- **Zustand**
  - Why: Minimal global state (sidebar, theme, user preferences)
  - Size: 1KB, no boilerplate

### Real-time

- **Supabase Realtime**
  - Why: PostgreSQL change data capture, WebSocket abstraction
  - Usage: Live request updates, notifications, collaborative editing

### PDF Generation

- **@react-pdf/renderer**
  - Why: React components → PDF, server-side rendering
  - Usage: Invoices, contracts, reports

## Backend

### Database

- **Supabase PostgreSQL**
  - Why: Managed PostgreSQL, built-in RLS, real-time, storage
  - Version: PostgreSQL 15
  - Features: Row-Level Security, JSONB, full-text search, vector embeddings

### ORM

- **Drizzle ORM**
  - Why: Lighter than Prisma, no schema drift, SQL-like syntax
  - Features: Type-safe queries, migrations, relations

### Authentication

- **Supabase Auth**
  - Why: Built-in bcrypt support (Laravel compatible), TOTP 2FA, OAuth providers
  - Features: Email/password, magic links, 15+ OAuth providers, JWT tokens

### Authorization

- **Custom RBAC** (built on Supabase)
  - Why: Replaces Spatie Permission, integrated with RLS
  - Tables: roles, permissions, role_permissions, user_roles
  - Functions: PostgreSQL functions for permission checks

### Storage

- **Supabase Storage**
  - Why: S3-compatible, RLS integration, image transformations
  - Buckets: documents (private), contracts (private), invoices (private), avatars (public)
  - Features: Resumable uploads, signed URLs, automatic optimization

### Background Jobs

- **Inngest**
  - Why: Event-driven, durable execution, built-in retries, free tier
  - Features: Cron scheduling, rate limiting, concurrency control, step functions
  - Free Tier: 10,000 steps/month

### Scheduled Tasks

- **Vercel Cron**
  - Why: Serverless, no infrastructure, integrated with Next.js
  - Usage: Trigger Inngest workflows, simple scheduled tasks

### Email

- **Resend**
  - Why: Developer-first, React Email templates, deliverability focus
  - Features: Email templates as React components, webhooks, analytics
  - Free Tier: 3,000 emails/month

### Payments

- **Stripe**
  - Why: Industry standard, comprehensive API, webhooks
  - Features: Payment Intents, Subscriptions, Invoicing, Customer Portal
  - SDK: @stripe/stripe-js, stripe (Node)

## Third-Party Services

### AI Providers

- **Vercel AI SDK**
  - Why: Unified interface for OpenAI, Anthropic, Google
  - Features: Streaming responses, tool calling, multi-provider support

- **OpenAI** (GPT-4 Turbo)
  - Usage: Document analysis, request triage

- **Anthropic** (Claude 3)
  - Usage: Long-context tasks, code generation

- **Google** (Gemini)
  - Usage: Multimodal tasks, vision

### Monitoring

- **Vercel Analytics**
  - Why: Built-in, privacy-friendly, Core Web Vitals
  - Features: Real User Monitoring, performance insights

- **Sentry**
  - Why: Error tracking, performance monitoring, release tracking
  - Features: Source maps, breadcrumbs, user feedback

### Feature Flags

- **Vercel Flags**
  - Why: Edge-based, no latency, A/B testing support
  - Usage: Gradual rollouts, beta features, kill switches

## Development Tools

### Package Manager

- **pnpm**
  - Why: Faster than npm/yarn, disk space efficient, monorepo support
  - Version: 9+

### Testing

#### Unit Testing

- **Vitest**
  - Why: Fast (Vite-powered), Jest-compatible API
  - Config: TypeScript, jsdom, coverage

#### Integration Testing

- **Playwright**
  - Why: Multi-browser, headless, screenshot/video recording
  - Usage: E2E tests, critical user flows

#### API Testing

- **MSW (Mock Service Worker)**
  - Why: Network-level mocking, works in tests and browser
  - Usage: API mocking for tests

### Linting & Formatting

- **ESLint**
  - Config: Next.js recommended, TypeScript, React Hooks

- **Prettier**
  - Config: Tailwind plugin for class sorting

### Type Checking

- **TypeScript**
  - Config: Strict mode, path aliases, incremental builds

### Git Hooks

- **Husky**
  - Why: Enforce pre-commit checks
  - Hooks: lint-staged, type checking, tests

## Deployment

### Hosting

- **Vercel**
  - Why: Next.js creators, edge network, automatic previews
  - Features: Edge Functions, Incremental Static Regeneration, Image Optimization
  - Regions: Multi-region for low latency

### Database

- **Supabase**
  - Plan: Pro ($25/month)
  - Features: 8GB database, daily backups, point-in-time recovery
  - Region: Same as Vercel for low latency

### CDN

- **Vercel Edge Network**
  - Why: Built-in, global distribution
  - Features: Automatic HTTPS, DDoS protection

### CI/CD

- **GitHub Actions**
  - Why: Native to GitHub, free for public repos
  - Workflows: Lint → Test → Build → Deploy
  - Preview: Automatic for PRs

## Development Environment

### Required Software

```bash
# Node.js 20+
node -v

# pnpm 9+
pnpm -v

# Git
git --version

# PostgreSQL (for local development)
psql --version
```

### VS Code Extensions

- ESLint
- Prettier
- Tailwind CSS IntelliSense
- Error Lens
- TypeScript Vue Plugin (Volar)
- Playwright Test for VSCode

### Environment Variables

#### Development (.env.local)

```bash
# Supabase
NEXT_PUBLIC_SUPABASE_URL=https://xxx.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
SUPABASE_SERVICE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

# Stripe
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Resend
RESEND_API_KEY=re_...

# OpenAI
OPENAI_API_KEY=sk-...

# Anthropic
ANTHROPIC_API_KEY=sk-ant-...

# Google
GOOGLE_API_KEY=AIza...

# Inngest
INNGEST_EVENT_KEY=...
INNGEST_SIGNING_KEY=...

# Sentry
SENTRY_DSN=https://...@sentry.io/...

# App
NEXT_PUBLIC_APP_URL=http://localhost:3000
```

## Architecture Decisions

### Why Next.js App Router?

- Server Components reduce client JS by 70%+
- Streaming for better perceived performance
- Built-in data fetching with fetch cache
- File-based routing with nested layouts

### Why Supabase over Firebase?

- PostgreSQL > Firestore for relational data
- Row-Level Security > Cloud Firestore Rules
- Open source, self-hostable if needed
- Better pricing for high data volumes

### Why Drizzle over Prisma?

- No schema migration conflicts with Supabase
- Lighter weight (no generation step)
- Better TypeScript inference
- SQL-like syntax (easier for Laravel devs)

### Why shadcn/ui over Material-UI?

- Copy-paste (no NPM bloat)
- Tailwind-native (no CSS-in-JS conflicts)
- Accessible by default (Radix UI)
- Server Components compatible

### Why Inngest over Bull/BullMQ?

- No Redis infrastructure needed
- Durable execution (no lost jobs)
- Built-in retries, rate limiting
- Event-driven architecture
- Better observability

## Migration-Specific Tools

### Data Migration

- **mysql2** - MySQL client for Node.js
- **@supabase/supabase-js** - Supabase client for data insertion
- **pg** - PostgreSQL client for validation

### Schema Conversion

- **pgloader** - Automated MySQL to PostgreSQL conversion
- Manual refinement for Laravel-specific patterns

### Testing Migration

- **Faker.js** - Generate test data
- **k6** - Load testing migrated APIs

## Performance Targets

| Metric                   | Target | How                               |
| ------------------------ | ------ | --------------------------------- |
| First Contentful Paint   | <1.5s  | Server Components, edge caching   |
| Largest Contentful Paint | <2.5s  | Image optimization, lazy loading  |
| Time to Interactive      | <3s    | Code splitting, minimal client JS |
| Cumulative Layout Shift  | <0.1   | Reserved space for images/ads     |
| API Response Time (p95)  | <300ms | Edge Functions, database indexes  |
| Background Job Latency   | <5s    | Inngest step functions            |

## Cost Estimates

### Monthly Costs (Production)

| Service   | Plan    | Cost                 |
| --------- | ------- | -------------------- |
| Vercel    | Pro     | $20                  |
| Supabase  | Pro     | $25                  |
| Inngest   | Starter | $0 (free tier)       |
| Resend    | Free    | $0 (3k emails/month) |
| Sentry    | Team    | $26                  |
| **Total** |         | **$71/month**        |

**Savings vs Laravel**:

- No dedicated server ($100-200/month)
- No Redis server ($20/month)
- No queue worker server ($50/month)
- **Total savings**: $100-200/month

## Scalability

### Database

- Supabase: Up to 100GB included
- Read replicas for heavy read workloads
- Connection pooling (PgBouncer)

### Edge Functions

- Automatically scales with traffic
- Cold start: <100ms
- Global distribution

### Storage

- Unlimited files
- Auto-scaling bandwidth
- CDN distribution

### Background Jobs

- Inngest: 10k steps/month free → $250/month for 1M steps
- Automatic retries, concurrency control
- No infrastructure management

## Security

### Authentication

- Supabase Auth: SOC 2 Type II compliant
- TOTP 2FA support
- OAuth with PKCE flow

### Database

- Row-Level Security (RLS) enforced
- Encrypted at rest (AES-256)
- Encrypted in transit (TLS 1.3)

### API

- CORS configured for production domain only
- Rate limiting on all endpoints
- Input validation with Zod

### Infrastructure

- Vercel: SOC 2 Type II compliant
- DDoS protection included
- Automatic security patches

## Compliance

### GDPR

- User data export (Supabase API)
- Right to deletion (cascading deletes)
- Audit logs (activity_logs table)

### Data Residency

- EU customers: Supabase EU region
- US customers: Supabase US region

## Support & Documentation

### Official Docs

- [Next.js](https://nextjs.org/docs)
- [Supabase](https://supabase.com/docs)
- [shadcn/ui](https://ui.shadcn.com)
- [Drizzle ORM](https://orm.drizzle.team)
- [Inngest](https://inngest.com/docs)

### Community

- Next.js Discord
- Supabase Discord
- Stack Overflow

### Internal

- Migration docs: `/migration/docs/`
- API reference: Generated with TypeDoc
- Component Storybook: Deployed to Vercel

---

**Last Updated**: Migration planning phase
**Next Review**: After Week 1 (database migration complete)
