# Quick Fix Guide: Support Tickets Table Error

## The Problem

```
Error: Could not find the table 'public.support_tickets' in the schema cache
```

The support tickets page is failing because the database tables don't exist yet.

## The Solution (3 Quick Steps)

### Step 1: Get Your Database URL

Find your database connection string from one of these sources:

**From Supabase Dashboard:**
1. Go to https://app.supabase.com
2. Select your project
3. Go to Settings → Database
4. Copy the "Connection string" (URI format)
5. Replace `[YOUR-PASSWORD]` with your actual database password

**From your .env.local file:**
```bash
cat .env.local | grep DATABASE_URL
```

### Step 2: Run the Migration

Choose the easiest method for you:

#### Method A: One-Line Fix (Fastest)

```bash
export DATABASE_URL="your-connection-string-here"
./scripts/run-support-tickets-migration.sh
```

#### Method B: Via Supabase Dashboard (No CLI needed)

1. Go to https://app.supabase.com
2. Navigate to **SQL Editor**
3. Click **New Query**
4. Copy the contents of `lib/db/migrations/004_create_support_tickets_tables.sql`
5. Paste and click **Run**

#### Method C: Direct psql (If you have PostgreSQL installed)

```bash
psql "your-connection-string-here" -f lib/db/migrations/004_create_support_tickets_tables.sql
```

### Step 3: Verify & Test

Refresh your application page:
```
http://localhost:3000/support
```

The error should be gone! You should now see an empty support tickets list.

## What This Migration Does

✅ Creates `support_tickets` table with all required columns  
✅ Creates `support_ticket_comments` table for ticket comments  
✅ Sets up Row-Level Security (RLS) for multi-tenant isolation  
✅ Adds indexes for fast queries  
✅ Creates auto-update triggers  
✅ Configures proper foreign key relationships  

## Need More Help?

- **Detailed Documentation**: See [MIGRATION_FIX.md](MIGRATION_FIX.md)
- **Migration Files**: Check [lib/db/migrations/README.md](lib/db/migrations/README.md)
- **Troubleshooting**: See the "Troubleshooting" section in MIGRATION_FIX.md

## Common Issues

### "psql: command not found"

**Solution**: Use Method B (Supabase Dashboard) instead - no installation needed!

### "permission denied for schema public"

**Solution**: Your database user needs permissions. Run this in SQL Editor:

```sql
GRANT ALL ON SCHEMA public TO postgres;
```

### "relation already exists"

**Solution**: Tables are already created! Just refresh your app.

## Files Changed in This Fix

- ✅ `lib/db/migrations/004_create_support_tickets_tables.sql` - The migration SQL
- ✅ `lib/db/migrations/README.md` - Migration documentation
- ✅ `scripts/run-support-tickets-migration.sh` - Automated migration runner
- ✅ `MIGRATION_FIX.md` - Detailed documentation
- ✅ `QUICK_FIX_GUIDE.md` - This quick start guide

---

**That's it!** Your support tickets feature should now be working. 🎉
