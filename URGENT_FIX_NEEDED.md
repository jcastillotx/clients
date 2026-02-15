# 🚨 URGENT: Support Tickets Table Missing

## Current Issue

Your application is currently failing with this error:

```
Error: Could not find the table 'public.support_tickets' in the schema cache
```

**Impact**: The Support Tickets feature at `/support` is completely non-functional.

## What Happened

The database tables for Support Tickets were never created in your PostgreSQL database. The migration SQL file exists in this repository, but it hasn't been applied to your database yet.

## ✅ The Fix (Choose One Method)

### Method 1: Node.js Script (Easiest)

```bash
# 1. Make sure you have a .env.local file with DATABASE_URL
# OR set DATABASE_URL environment variable

# 2. Run the migration script
node scripts/apply-migration.js
```

The script will:
- ✅ Validate your database connection
- ✅ Apply the migration
- ✅ Verify tables were created
- ✅ Show detailed success/error messages

### Method 2: Using pnpm (If Drizzle is configured)

```bash
# This uses Drizzle Kit to push schema changes
pnpm db:push
```

### Method 3: Via Supabase Dashboard (No CLI needed)

**This is the recommended method if you don't have database credentials locally.**

1. **Open Supabase Dashboard**
   - Go to: https://app.supabase.com
   - Select your project

2. **Open SQL Editor**
   - Navigate to: **SQL Editor** (left sidebar)
   - Click: **New Query**

3. **Copy Migration SQL**
   - Open file: `lib/db/migrations/004_create_support_tickets_tables.sql`
   - Copy the ENTIRE contents (all 210 lines)

4. **Run Migration**
   - Paste into SQL Editor
   - Click: **Run** (or press Ctrl+Enter)
   - Wait for success message (~5-10 seconds)

5. **Verify Success**
   - You should see: "Success. No rows returned"
   - Or: Table creation confirmation

### Method 4: Direct psql Command

```bash
# Set your database URL
export DATABASE_URL="postgresql://postgres:YOUR_PASSWORD@db.xxx.supabase.co:5432/postgres"

# Run migration
psql "$DATABASE_URL" -f lib/db/migrations/004_create_support_tickets_tables.sql
```

## 📋 Database Credentials

You need ONE of these environment variables:

- `DATABASE_URL` - PostgreSQL connection string
- `POSTGRES_URL_NON_POOLING` - Vercel Postgres (non-pooling)
- `POSTGRES_URL` - Vercel Postgres (pooling)

### Where to Find Database Credentials

#### For Supabase:
1. Go to: https://app.supabase.com
2. Select your project
3. Navigate to: **Settings** → **Database**
4. Copy: **Connection String** (URI format)
5. Replace `[YOUR-PASSWORD]` with your actual database password

Example:
```
postgresql://postgres:your_password@db.abcdefghijk.supabase.co:5432/postgres
```

#### For Vercel Postgres:
1. Go to: https://vercel.com/dashboard
2. Select your project
3. Navigate to: **Storage** → **Connect Store**
4. Copy environment variables shown

### How to Set Locally

Create `.env.local` file in project root:

```bash
# .env.local
DATABASE_URL="postgresql://postgres:your_password@db.xxx.supabase.co:5432/postgres"
```

**Important**: Never commit `.env.local` to git (it's already in `.gitignore`)

## 🔍 Verification Steps

After applying the migration:

### 1. Check Tables Exist (SQL)

```sql
-- Run in Supabase SQL Editor
SELECT table_name, 
       (SELECT COUNT(*) FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = t.table_name) as columns
FROM information_schema.tables t
WHERE table_schema = 'public' 
  AND table_name IN ('support_tickets', 'support_ticket_comments');
```

Expected result:
```
table_name                  | columns
---------------------------+--------
support_tickets            | 34
support_ticket_comments    | 8
```

### 2. Test the Application

```bash
# If running locally
pnpm dev

# Then visit:
http://localhost:3000/support
```

**Expected**: Page loads without errors and shows empty ticket list

### 3. Create Test Ticket

- Click "New Ticket" button
- Fill in form fields
- Submit
- Verify ticket appears in list

## 🎯 What the Migration Creates

The migration creates:

✅ **2 Database Tables**:
- `support_tickets` (34 columns) - Main tickets table
- `support_ticket_comments` (8 columns) - Comments on tickets

✅ **12 Performance Indexes** for fast queries

✅ **10 Row-Level Security Policies** for multi-tenant isolation

✅ **Auto-Update Triggers** for timestamp management

✅ **Foreign Key Constraints** for data integrity

## 🔐 Security Features

- **Row-Level Security (RLS)**: Each client can only see their own tickets
- **Multi-Tenant Isolation**: Database-level data separation
- **Audit Trail**: Created/updated timestamps on all records
- **Soft Deletes**: Data is never permanently deleted

## ⚠️ Troubleshooting

### Error: "relation already exists"

**Solution**: Tables are already created! Just refresh your app - it should work now.

### Error: "password authentication failed"

**Solution**: Check your database password in the connection string.

### Error: "connection refused"

**Solutions**:
- Check database server is running
- Verify connection string is correct
- Check firewall/network settings
- For Supabase: Ensure project is not paused

### Error: "permission denied"

**Solution**: Your database user needs permissions. Run in SQL Editor:

```sql
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON ALL TABLES IN SCHEMA public TO postgres;
```

### Still Getting Errors?

1. **Check Logs**: Look for detailed error messages
2. **Verify Dependencies**: Run `pnpm install`
3. **Restart Server**: Stop and restart your Next.js dev server
4. **Clear Cache**: Delete `.next` folder and rebuild

## 📁 Files Involved

- **Migration SQL**: `lib/db/migrations/004_create_support_tickets_tables.sql`
- **Migration Script**: `scripts/apply-migration.js` (NEW)
- **Shell Script**: `scripts/run-support-tickets-migration.sh`
- **TypeScript Schema**: `lib/db/schema/support-tickets.ts`

## 🆘 Need Help?

1. **Quick Start**: See `QUICK_FIX_GUIDE.md`
2. **Detailed Docs**: See `MIGRATION_FIX.md`
3. **Issue Tracking**: See `ISSUE_8270_FIX.md`
4. **Full Report**: See `COMPLETION_REPORT.md`

## ✨ After Migration Success

Once the migration is applied:

1. ✅ `/support` page will load
2. ✅ Can create new tickets
3. ✅ Can view ticket lists
4. ✅ Can add comments
5. ✅ Can filter/search tickets
6. ✅ SLA tracking works
7. ✅ All features functional

## 📝 Summary

**Problem**: Missing database tables  
**Solution**: Apply migration (one of 4 methods above)  
**Time Required**: 5-10 minutes  
**Difficulty**: Easy  

**Status**: 🔴 Migration NOT applied yet - database tables missing

**After applying**: 🟢 Migration applied - feature fully functional

---

## Quick Command Reference

```bash
# Method 1: Node.js script
node scripts/apply-migration.js

# Method 2: Drizzle Kit
pnpm db:push

# Method 3: Shell script (requires psql)
export DATABASE_URL="postgresql://..."
./scripts/run-support-tickets-migration.sh

# Method 4: Direct psql
psql "$DATABASE_URL" -f lib/db/migrations/004_create_support_tickets_tables.sql

# Verify tables
psql "$DATABASE_URL" -c "\dt public.support_tickets"
```

---

**Created**: February 15, 2026  
**Issue**: #8270 - Missing support_tickets table  
**Priority**: 🚨 URGENT - Feature non-functional  
**Action Required**: Apply migration using one of the methods above
