# AGENTS.md

Agent operating guide for `/Users/jlaptop/Apps/clients`.
This repo is a Next.js 15 + TypeScript + Supabase monorepo-style app (single package).

## 1) Quick Start

- Package manager: `pnpm` only (`preinstall` enforces this).
- Node version: `>=20` (see `package.json` engines).
- Install deps: `pnpm install`.
- Start dev server: `pnpm dev` (http://localhost:3000).

## 2) Build, Lint, Typecheck, Test Commands

Primary commands (from `package.json`):

- `pnpm dev` - run Next.js dev server.
- `pnpm build` - production build.
- `pnpm start` - run built app.
- `pnpm lint` - ESLint via Next config.
- `pnpm type-check` - TypeScript `tsc --noEmit`.
- `pnpm test` - unit tests (Vitest).
- `pnpm test:e2e` - end-to-end tests (Playwright).

Database / schema commands:

- `pnpm db:generate` - generate Drizzle migrations.
- `pnpm db:push` - push Drizzle schema to DB.
- `pnpm db:studio` - open Drizzle Studio.
- `pnpm db:migrate` - apply migration helper script.

## 3) Single-Test Execution (Important)

Vitest single file:

- `pnpm test -- path/to/file.test.ts`

Vitest single test name:

- `pnpm test -- -t "test name"`
- You can combine name + file: `pnpm test -- -t "test name" path/to/file.test.ts`

Playwright single file:

- `pnpm test:e2e -- path/to/file.spec.ts`

Playwright single test title:

- `pnpm test:e2e -- -g "test title"`

Useful CI-style variants:

- `pnpm test -- --run`
- `pnpm test:e2e -- --project=chromium`

## 4) Required Agent Workflow

Before opening a PR or finishing major edits, run:

1. `pnpm type-check`
2. `pnpm lint`
3. `pnpm test -- --run` (or targeted tests if suite is missing/slow)

If tests do not exist for changed code, add tests where practical or note gap explicitly.

## 5) Cursor / Copilot Rules Status

- `.github/copilot-instructions.md` exists and is authoritative guidance.
- `.cursor/rules/` and `.cursorrules` were not found in this repository.

Rules imported from Copilot instructions:

- Prefer Server Components; use Client Components only for interactivity.
- Enforce multi-tenant boundaries (`client_id`) and RBAC checks.
- Follow API route pattern under `app/api/**`.
- Use Zod validation for API/form inputs.
- Use `@/` path alias imports.

## 6) Architecture Constraints You Must Respect

- Framework: Next.js App Router (`app/(auth)`, `app/(dashboard)`, `app/api/**`).
- Auth/session: Supabase SSR + middleware (`middleware.ts`, `lib/supabase/middleware.ts`).
- DB: PostgreSQL (Supabase) + Drizzle schema in `lib/db/schema/**`.
- Authorization: RBAC utilities in `lib/rbac/**` plus RLS at DB layer.
- Background jobs: Inngest handlers under `lib/inngest/functions/**`.

## 7) Code Style and Formatting

Formatting baseline (inferred from repo + Prettier dependency):

- Use TypeScript for all app code.
- Use 2-space indentation.
- Use semicolons.
- Prefer double quotes (`"`) consistently.
- Keep trailing commas where formatter applies.
- Keep lines readable; split long function calls over multiple lines.

ESLint baseline:

- Config extends `next/core-web-vitals` and `next/typescript`.
- Treat lint warnings as actionable even though `next.config.mjs` temporarily ignores lint during build.

## 8) Imports and Module Organization

Import ordering convention (follow existing patterns):

1. External packages (`react`, `next/*`, third-party libs).
2. Internal alias imports (`@/lib/...`, `@/components/...`).
3. Relative imports (`./...`) only when same module area.

Import rules:

- Prefer `@/` alias over deep relative paths.
- Import types with `import type` when only types are needed.
- Avoid wildcard/barrel imports when they hide ownership.

## 9) TypeScript Rules

- `strict: true` is enabled; keep code strict-clean.
- Do not introduce `any`; use `unknown` + narrowing/type guards.
- Prefer explicit DTO/schema types for API input and output.
- Reuse inferred types from Zod (`z.infer`) and Drizzle (`$inferSelect`, `$inferInsert`).
- Model nullability explicitly (`string | null`) when reading DB rows.

## 10) Naming Conventions

- React components: PascalCase exports, kebab-case filenames in `components/**`.
- Hooks/util functions: camelCase.
- Constants/enums: UPPER_SNAKE_CASE for constants, PascalCase for enum-like objects/types.
- DB columns: snake_case in SQL/Supabase payloads.
- App-level TS fields may be camelCase; map to snake_case at API/DB boundaries.
- Route handlers must be named `GET`, `POST`, `PATCH`, `DELETE` etc.

## 11) API and Validation Patterns

- Validate request payloads with Zod before writes.
- Authenticate first (`supabase.auth.getUser()`), then authorize (RBAC/role checks).
- Respect feature flags and tenant scope where relevant.
- Use `NextResponse.json(...)` with clear status codes.
- Keep route files thin; push reusable logic into `lib/**`.

## 12) Error Handling and Logging

- Wrap API handlers in `try/catch` when they perform IO.
- Return user-safe errors; avoid leaking secrets or stack traces.
- Log with context (`console.error("Error creating X:", error)`).
- Use 401 for unauthenticated, 403 for unauthorized, 400 for validation, 500 for unexpected failures.
- For expected empty states, return structured success responses instead of throwing.

## 13) Frontend and UI Conventions

- Default to Server Components; add `"use client"` only when needed.
- Use shadcn/ui primitives from `components/ui/**`.
- Build forms with React Hook Form + Zod resolver.
- Keep Tailwind classes composable and consistent with existing design tokens.
- Maintain accessibility: labels, keyboard support, semantic elements.

## 14) Security and Environment Hygiene

- Never commit secrets or real keys.
- Use `.env.local.example` as the reference for required env vars.
- Preserve auth middleware behavior; do not break session cookie forwarding.
- Maintain rate limiting and MFA checks on sensitive auth/admin paths.
- Assume RLS is a hard requirement; never bypass tenant isolation intentionally.

## 15) Definition of Done for Agent Changes

- Code compiles with `pnpm type-check`.
- Lint passes with `pnpm lint` (or issues documented).
- Relevant tests pass (targeted or full run).
- New/changed behavior is documented in PR notes or docs when needed.
- Changes follow conventions in this file and `.github/copilot-instructions.md`.
