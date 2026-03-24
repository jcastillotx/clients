# GEMINI.md - Instructional Context for KRE8IV Clients

This file provides the foundational context, architectural patterns, and engineering standards for the **KRE8IV Clients** project.

## Project Overview

**KRE8IV Clients** is a modern, multi-tenant client management platform (SaaS) built for high-performance client operations. It handles client onboarding, service requests, invoicing, payments, document management, and contract lifecycles.

### Tech Stack
- **Framework:** Next.js 15 (App Router)
- **Language:** TypeScript 5.3+
- **Database:** PostgreSQL (Supabase) + Drizzle ORM
- **Auth & Security:** Supabase Auth (SSR) + RBAC + MFA + Rate Limiting
- **Styling:** Tailwind CSS 3.4 + shadcn/ui (Radix UI)
- **Background Jobs:** Inngest
- **Payments:** Stripe
- **Email:** Resend
- **Validation:** Zod + React Hook Form

---

## Core Architecture & Patterns

### 1. Authentication & Authorization
- **Auth:** Managed via Supabase Auth with SSR session refresh in `middleware.ts`.
- **RBAC:** Fine-grained Role-Based Access Control via `lib/db/schema/rbac.ts`.
  - Roles: `super_admin`, `admin`, `account_manager`, `staff`, `client`.
  - Permissions are resource-based (e.g., `clients.read`, `invoices.create`).
  - **Authoritative Checks:** Always verify against the DB (`user_roles` table) or `dbUser.is_super_admin` flag. Do NOT rely solely on JWT metadata for sensitive operations.
  - Helper functions: `lib/rbac/check.ts` and `lib/rbac/permissions.ts`.

### 2. Data Layer (Drizzle + Supabase)
- **Schema:** Defined in `lib/db/schema/` with a modular structure.
- **Migrations:** Managed via `drizzle-kit` and Supabase CLI.
- **RLS:** Row-Level Security is strictly enforced at the database level to ensure tenant isolation.
- **DB Connection:** Initialized in `lib/db/index.ts` using `postgres.js`.

### 3. Server-First Mentality
- **Server Components:** Default choice for data fetching and layout.
- **Server Actions:** Used for mutations and form submissions.
- **Client Components:** Reserved for interactive UI elements (e.g., charts, complex forms).

### 4. Background Processing
- **Inngest:** Handles asynchronous tasks like invoice generation, SLA monitoring, and email automation.
- Functions located in `lib/inngest/`.

---

## Engineering Standards

### Coding Style & Conventions
- **Type Safety:** Absolute requirement. Avoid `any`. Use Zod for runtime validation and Drizzle for type-safe queries.
- **Naming:** CamelCase for variables/functions, PascalCase for components/types.
- **Imports:** Use absolute paths with `@/` prefix (e.g., `@/lib/db`).
- **Standard Tooling:** `pnpm` is the mandated package manager.

### Security Mandates
- **Secrets:** Never commit `.env` files. Use environment variables exclusively.
- **Input Validation:** Every user input must be validated with Zod.
- **Admin Access:** MFA is required for admin-sensitive routes (`/admin`, `/integrations`).
- **Rate Limiting:** Auth routes and sensitive APIs are protected via `lib/rate-limit.ts`.

### Testing Strategy
- **Unit Tests:** `vitest`
- **E2E Tests:** `playwright`
- **Type Checking:** `pnpm type-check`

---

## Key Commands

| Task | Command |
| :--- | :--- |
| Development | `pnpm dev` |
| Build | `pnpm build` |
| Type Check | `pnpm type-check` |
| Lint | `pnpm lint` |
| Unit Tests | `pnpm test` |
| E2E Tests | `pnpm test:e2e` |
| DB Generate | `pnpm db:generate` |
| DB Push | `pnpm db:push` |
| DB Studio | `pnpm db:studio` |

---

## Directory Structure

- `app/`: Next.js App Router (Auth, Dashboard, API routes).
- `components/`: UI components (feature-specific + `ui/` for shadcn).
- `lib/`: Core logic (db, auth, rbac, inngest, stripe, supabase).
- `docs/`: Supplemental documentation (deployment, background jobs).
- `scripts/`: Utility scripts for migrations and setup.

## Development Workflow

1.  **Analyze:** Research existing patterns in `lib/` and `app/`.
2.  **Schema:** Update `lib/db/schema/` and run `pnpm db:generate`.
3.  **Logic:** Implement core logic in `lib/` (actions, helpers).
4.  **UI:** Build components using shadcn/ui and Tailwind.
5.  **Validate:** Ensure RLS and RBAC are properly applied.
6.  **Verify:** Run lint, type-check, and relevant tests.
