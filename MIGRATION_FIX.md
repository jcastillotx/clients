# Support Tickets Table Migration Fix

## Issue

The application was throwing an error when accessing the Support Tickets page:

```
Error fetching tickets: {
  code: 'PGRST205',
  details: null,
  hint: null,
  message: "Could not find the table 'public.support_tickets' in the schema cache"
}
```

## Root Cause

The `support_tickets` and `support_ticket_comments` tables were not created in the database. While the TypeScript schema definitions existed in `lib/db/schema/support-tickets.ts`, the corresponding SQL tables were never created in PostgreSQL.

## Solution

A new migration file has been created: `lib/db/migrations/004_create_support_tickets_tables.sql`

This migration creates:
- `public.support_tickets` table with all required columns
- `public.support_ticket_comments` table for ticket comments
- Indexes for performance optimization
- Row-Level Security (RLS) policies for multi-tenant data isolation
- Auto-update triggers for the `updated_at` column
- Proper foreign key relationships

## How to Apply the Fix

### Option 1: Using the Migration Script (Recommended)

```bash
# Set your database connection string
export DATABASE_URL="postgresql://postgres:[PASSWORD]@db.xxx.supabase.co:5432/postgres"

# Run the migration script
./scripts/run-support-tickets-migration.sh
```

### Option 2: Using psql Directly

```bash
# Set your database connection string
export DATABASE_URL="postgresql://postgres:[PASSWORD]@db.xxx.supabase.co:5432/postgres"

# Run the migration
psql $DATABASE_URL -f lib/db/migrations/004_create_support_tickets_tables.sql
```

### Option 3: Via Supabase Dashboard

1. Log in to your Supabase project at https://app.supabase.com
2. Navigate to **SQL Editor**
3. Click **New Query**
4. Copy the entire contents of `lib/db/migrations/004_create_support_tickets_tables.sql`
5. Paste into the SQL editor
6. Click **Run** to execute the migration

### Option 4: Using Drizzle Kit (if configured)

```bash
# Push schema changes to database
pnpm db:push
```

## Verifying the Fix

After running the migration, verify the tables were created:

```bash
# Connect to database
psql $DATABASE_URL

# List tables
\dt public.support_tickets

# View table structure
\d public.support_tickets

# Check if tables are empty
SELECT COUNT(*) FROM public.support_tickets;
SELECT COUNT(*) FROM public.support_ticket_comments;

# Exit psql
\q
```

Expected output:
```
 Schema |        Name        | Type  |  Owner   
--------+--------------------+-------+----------
 public | support_tickets    | table | postgres

                                       Table "public.support_tickets"
           Column           |           Type           | Collation | Nullable |      Default       
----------------------------+--------------------------+-----------+----------+--------------------
 id                         | uuid                     |           | not null | gen_random_uuid()
 client_id                  | uuid                     |           | not null | 
 ticket_number              | text                     |           | not null | 
 subject                    | text                     |           | not null | 
 status                     | text                     |           | not null | 'open'::text
 priority                   | text                     |           | not null | 'medium'::text
 ...
```

## Testing the Fix

1. **Start the development server:**
   ```bash
   pnpm dev
   ```

2. **Navigate to Support Tickets:**
   Visit http://localhost:3000/support

3. **Create a test ticket:**
   - Click "New Ticket" button
   - Fill in the form
   - Submit

4. **Verify ticket appears in list:**
   - Should see the ticket in the tickets list
   - No database errors in console

## RLS Policies Applied

The migration includes Row-Level Security policies to ensure multi-tenant data isolation:

- **SELECT**: Users can only view tickets belonging to their client
- **INSERT**: Users can only create tickets for their client
- **UPDATE**: Users can only update tickets belonging to their client
- **DELETE**: Users can only delete tickets belonging to their client

Comments have similar policies:
- Users can only see/create comments on tickets belonging to their client
- Users can only update/delete their own comments

## Database Schema

### support_tickets Table

| Column | Type | Description |
|--------|------|-------------|
| id | UUID | Primary key |
| client_id | UUID | Foreign key to clients table |
| ticket_number | TEXT | Unique ticket number (e.g., TKT-2024-001) |
| subject | TEXT | Ticket subject/title |
| description | TEXT | Ticket description |
| status | TEXT | open, in_progress, waiting_on_client, waiting_on_vendor, resolved, closed |
| priority | TEXT | low, medium, high, urgent |
| category | TEXT | technical, billing, general, feature_request, bug_report, security, performance |
| created_by | UUID | User who created the ticket |
| assigned_to | UUID | User assigned to the ticket |
| is_billable | BOOLEAN | Whether this ticket is billable |
| estimated_hours | DECIMAL | Estimated hours to resolve |
| actual_hours | DECIMAL | Actual hours spent |
| sla_response_due_at | TIMESTAMPTZ | When first response is due |
| sla_resolution_due_at | TIMESTAMPTZ | When resolution is due |
| sla_response_breached | BOOLEAN | Whether response SLA was breached |
| sla_resolution_breached | BOOLEAN | Whether resolution SLA was breached |
| escalation_level | INTEGER | Number of escalations (0 = not escalated) |
| metadata | JSONB | Additional metadata (tags, attachments, etc.) |
| created_at | TIMESTAMPTZ | When ticket was created |
| updated_at | TIMESTAMPTZ | When ticket was last updated |

### support_ticket_comments Table

| Column | Type | Description |
|--------|------|-------------|
| id | UUID | Primary key |
| support_ticket_id | UUID | Foreign key to support_tickets |
| user_id | UUID | User who created the comment |
| comment | TEXT | Comment text |
| is_internal | BOOLEAN | Whether comment is internal (not visible to client) |
| attachments | JSONB | File attachments |
| created_at | TIMESTAMPTZ | When comment was created |
| updated_at | TIMESTAMPTZ | When comment was last updated |

## Performance Optimizations

The migration includes indexes on frequently queried columns:
- `client_id` - For RLS filtering
- `created_by`, `assigned_to` - For user lookups
- `status`, `priority`, `category` - For filtering
- `ticket_number` - For unique lookups
- `created_at` - For date sorting
- `deleted_at` - For soft delete filtering

## Related Files

- **Schema Definition**: `lib/db/schema/support-tickets.ts`
- **Migration SQL**: `lib/db/migrations/004_create_support_tickets_tables.sql`
- **Migration Script**: `scripts/run-support-tickets-migration.sh`
- **Support Page**: `app/(dashboard)/support/page.tsx`
- **Support Components**: `components/support/`
- **API Routes**: `app/api/support/`

## Troubleshooting

### "relation already exists" Error

If you see this error, the tables may have already been created. You can:

1. Check if tables exist:
   ```sql
   \dt public.support_tickets
   ```

2. Drop existing tables (⚠️ WARNING: This will delete all data):
   ```sql
   DROP TABLE IF EXISTS public.support_ticket_comments CASCADE;
   DROP TABLE IF EXISTS public.support_tickets CASCADE;
   ```

3. Re-run the migration

### "permission denied" Error

Ensure your database user has sufficient permissions:

```sql
GRANT ALL PRIVILEGES ON DATABASE postgres TO your_user;
GRANT ALL ON SCHEMA public TO your_user;
```

### RLS Policy Errors

If you encounter RLS policy errors, ensure:
- The `auth.uid()` function is available
- The `users` table has a `client_id` column
- The `clients` table exists

## Next Steps

After applying this migration, you may want to:

1. **Seed sample data** (optional):
   ```sql
   -- Create a test ticket
   INSERT INTO public.support_tickets (
     client_id, created_by, ticket_number, subject, description, category, priority, status
   ) VALUES (
     'your-client-id', 'your-user-id', 'TKT-2024-001', 
     'Test Ticket', 'This is a test ticket', 'technical', 'medium', 'open'
   );
   ```

2. **Configure SLA rules** in the application settings

3. **Set up email notifications** for new tickets

4. **Create ticket templates** for common issues

5. **Configure escalation rules** for high-priority tickets

## References

- [Migration Files Documentation](lib/db/migrations/README.md)
- [Supabase RLS Documentation](https://supabase.com/docs/guides/auth/row-level-security)
- [Drizzle ORM Documentation](https://orm.drizzle.team/docs/overview)
- [Project Documentation](CLAUDE.md)
