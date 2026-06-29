# TypeScript / JavaScript Style Guide

## Core Rules

- **No `any` types** — use `unknown` and narrow, or define proper interfaces
- **Strict mode** — `tsconfig.json` has `"strict": true`; never disable it
- **Prefer `type` over `interface`** for object shapes; use `interface` only when declaration merging is needed
- **Named exports** over default exports — easier to refactor and grep
- **Explicit return types** on all exported functions

## Naming

- **Files**: `kebab-case.ts` / `kebab-case.tsx`
- **Components**: `PascalCase`
- **Functions/variables**: `camelCase`
- **Constants**: `UPPER_SNAKE_CASE` for module-level primitives
- **Types/interfaces**: `PascalCase`
- **Zod schemas**: suffix with `Schema` (e.g. `invoiceSchema`)
- **Server actions**: prefix with `action` (e.g. `actionCreateInvoice`)

## React / Next.js

- **Server Components by default** — add `"use client"` only when needed (event handlers, hooks, browser APIs)
- **No `useEffect` for data fetching** — use Server Components or TanStack Query
- **Props via destructuring** — `function Card({ title, children }: CardProps)`
- **Co-locate component files** — `components/invoices/invoice-card.tsx` not `components/invoice-card.tsx`
- Keep components under 200 lines; extract sub-components when they grow

## API Routes

```typescript
// Pattern for all API routes
export async function GET(request: Request) {
  const supabase = createClient()
  const { data, error } = await supabase.from('table').select('*')
  if (error) return Response.json({ error: error.message }, { status: 500 })
  return Response.json(data)
}
```

- Always validate request bodies with Zod before processing
- Return consistent shape: `{ data, error }` or `Response.json(payload, { status })`
- Use `createClient()` from `lib/supabase/server.ts` (never the browser client in routes)

## Zod Validation

```typescript
// lib/validations/invoice.ts
import { z } from 'zod'

export const createInvoiceSchema = z.object({
  clientId: z.string().uuid(),
  amount: z.number().positive(),
  dueDate: z.string().datetime(),
})

export type CreateInvoiceInput = z.infer<typeof createInvoiceSchema>
```

- Define schemas in `lib/validations/` and import them in both routes and forms
- Use `.safeParse()` in API routes so you control the error response

## Drizzle ORM

- Define all schemas in `lib/db/schema/` — one file per domain
- Use `db.select()`, `db.insert()`, `db.update()`, `db.delete()` — never raw SQL except in migrations
- Always include `client_id` in `where` clauses for multi-tenant queries
- Prefer `returning()` after mutations to avoid a second round-trip

## Error Handling

```typescript
// Explicit error handling — never swallow
const result = schema.safeParse(body)
if (!result.success) {
  return Response.json({ error: result.error.flatten() }, { status: 400 })
}
```

- Handle errors at every level — no silent catch blocks
- Log server-side context; return user-friendly messages to the client
- Use Sentry for unexpected errors in production

## Immutability

- Never mutate objects in place — use spread: `{ ...existing, field: newValue }`
- Prefer `const` over `let`; never `var`
- Arrays: use `map`, `filter`, `reduce` — avoid `push`/`splice` on shared references

## File Size

- Max 400 lines per file; 800 hard limit
- Extract utilities when a file grows; organize by feature domain, not by type

## Formatting

- 2-space indentation
- Single quotes for strings
- Trailing commas in multi-line structures
- Semicolons required
- Run `pnpm lint` before committing
