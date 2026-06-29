# SQL / PostgreSQL Style Guide

Covers raw SQL migrations, RLS policies, and Drizzle schema conventions.

## Naming Conventions

- **Tables**: `snake_case`, plural (e.g. `invoices`, `client_features`)
- **Columns**: `snake_case` (e.g. `created_at`, `client_id`)
- **Indexes**: `idx_{table}_{columns}` (e.g. `idx_invoices_client_id`)
- **Foreign keys**: `fk_{table}_{ref_table}` (e.g. `fk_invoices_clients`)
- **RLS policies**: descriptive, quoted (e.g. `"Users can only see their client invoices"`)
- **Functions/triggers**: `snake_case` verbs (e.g. `update_updated_at()`)

## Schema Conventions

Every table must have:

```sql
id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
client_id   UUID NOT NULL REFERENCES clients(id) ON DELETE CASCADE,
created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
updated_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
```

- Always use `UUID` for primary keys — never serial/integer PKs
- Always include `client_id` for multi-tenant isolation (except `clients`, `users`, system tables)
- Always include `created_at` / `updated_at` with `DEFAULT NOW()`
- Prefer `TIMESTAMPTZ` over `TIMESTAMP` — store in UTC, display in local time

## Row-Level Security (RLS)

RLS must be enabled on every table that holds tenant data:

```sql
-- Enable RLS
ALTER TABLE invoices ENABLE ROW LEVEL SECURITY;

-- SELECT policy
CREATE POLICY "Users can view their client invoices"
ON invoices FOR SELECT
USING (client_id = (auth.jwt() -> 'client_id')::uuid);

-- INSERT policy
CREATE POLICY "Users can create invoices for their client"
ON invoices FOR INSERT
WITH CHECK (client_id = (auth.jwt() -> 'client_id')::uuid);
```

- Always write separate policies for SELECT, INSERT, UPDATE, DELETE
- Service role bypasses RLS — only use it in trusted server contexts
- Test RLS policies with `SET role = authenticated; SET request.jwt.claims = '{"client_id": "..."}'`

## Migrations

- Migration files live in `lib/db/migrations/` — named `NNN_description.sql`
- Migrations are append-only — never edit an applied migration
- Every migration must be idempotent where possible (`CREATE TABLE IF NOT EXISTS`, `ALTER TABLE IF EXISTS`)
- Add an index for every foreign key column

```sql
-- Always index FKs
CREATE INDEX IF NOT EXISTS idx_invoices_client_id ON invoices(client_id);
CREATE INDEX IF NOT EXISTS idx_invoices_status ON invoices(status);
```

## Query Patterns (Drizzle)

```typescript
// Always filter by client_id
const invoices = await db
  .select()
  .from(invoicesTable)
  .where(eq(invoicesTable.clientId, clientId))
  .orderBy(desc(invoicesTable.createdAt))

// Prefer returning() to avoid double queries
const [created] = await db
  .insert(invoicesTable)
  .values(data)
  .returning()
```

## Performance

- Index columns used in `WHERE`, `ORDER BY`, and `JOIN` clauses
- Use `EXPLAIN ANALYZE` to verify query plans before adding indexes blindly
- Prefer partial indexes for filtered queries (e.g. `WHERE status = 'active'`)
- Avoid `SELECT *` — always specify columns in application queries
- For large tables, use `LIMIT` + cursor pagination over `OFFSET`

## Soft Deletes

Where deletion history matters, use soft deletes:

```sql
deleted_at TIMESTAMPTZ,  -- NULL = active, timestamp = deleted
```

Add RLS to exclude soft-deleted rows:
```sql
USING (client_id = ... AND deleted_at IS NULL)
```

## Supabase-Specific

- Use `auth.uid()` for the current user's UUID
- Use `auth.jwt() -> 'client_id'` for the tenant ID from the JWT
- Realtime subscriptions respect RLS — no extra filtering needed in the client
- Storage bucket policies mirror RLS — keep them in sync
