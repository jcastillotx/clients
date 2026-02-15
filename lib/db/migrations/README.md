# Database Migrations

This directory contains SQL migration files for the Kre8iv Clients Platform database.

## Migration Files

Migrations are numbered sequentially and should be run in order:

1. `001_create_rbac_tables.sql` - Role-Based Access Control tables
2. `002_create_template_tables.sql` - Email and invoice template tables
3. `003_create_document_tables.sql` - Document library tables
4. `004_create_support_tickets_tables.sql` - Support tickets and comments tables
5. `010_feature_flags.sql` - Feature flags system

## Running Migrations

### Prerequisites

- PostgreSQL database (Supabase recommended)
- Database credentials configured in `.env.local`
- `psql` command-line tool installed

### Method 1: Using psql (Recommended)

Connect to your database and run migrations in order:

```bash
# Set your database connection string
export DATABASE_URL="postgresql://postgres:[PASSWORD]@db.xxx.supabase.co:5432/postgres"

# Run migrations in order
psql $DATABASE_URL -f lib/db/migrations/001_create_rbac_tables.sql
psql $DATABASE_URL -f lib/db/migrations/002_create_template_tables.sql
psql $DATABASE_URL -f lib/db/migrations/003_create_document_tables.sql
psql $DATABASE_URL -f lib/db/migrations/004_create_support_tickets_tables.sql
psql $DATABASE_URL -f lib/db/migrations/010_feature_flags.sql
```

### Method 2: Run all migrations at once

```bash
for file in lib/db/migrations/*.sql; do
  echo "Running migration: $file"
  psql $DATABASE_URL -f "$file"
done
```

### Method 3: Via Supabase Dashboard

1. Log in to your Supabase project dashboard
2. Go to **SQL Editor**
3. Create a new query
4. Copy and paste the contents of each migration file
5. Run the query
6. Repeat for all migration files in order

### Method 4: Using Drizzle Kit

If you have all schemas defined in TypeScript:

```bash
# Generate migrations from schemas
pnpm db:generate

# Push schema changes directly to database
pnpm db:push
```

## Verifying Migrations

After running migrations, verify tables were created:

```sql
-- List all tables
\dt

-- Verify support_tickets table
\d public.support_tickets

-- Check row count
SELECT COUNT(*) FROM public.support_tickets;
```

## Rollback

To rollback migrations, you'll need to manually drop tables or create rollback scripts. Example:

```sql
-- Rollback support tickets migration
DROP TABLE IF EXISTS public.support_ticket_comments CASCADE;
DROP TABLE IF EXISTS public.support_tickets CASCADE;
```

## Migration Best Practices

1. **Always backup** your database before running migrations
2. **Test migrations** in a development/staging environment first
3. **Run migrations in order** - respect the numbering
4. **Never modify** existing migration files after they've been deployed
5. **Create new migrations** for schema changes instead of modifying old ones

## Troubleshooting

### Error: "relation already exists"

This means the table was already created. You can either:
- Skip this migration
- Use `CREATE TABLE IF NOT EXISTS` (already in migrations)
- Drop the existing table if safe to do so

### Error: "permission denied"

Ensure your database user has sufficient permissions:

```sql
GRANT ALL PRIVILEGES ON DATABASE postgres TO your_user;
GRANT ALL ON SCHEMA public TO your_user;
```

### Error: "database connection failed"

Check your DATABASE_URL environment variable and ensure:
- Database server is running
- Credentials are correct
- Firewall allows connection
- SSL/TLS settings are correct

## Support

For issues with migrations:
1. Check migration file for syntax errors
2. Verify database connection
3. Review Supabase logs
4. Check PostgreSQL version compatibility (requires PostgreSQL 14+)
