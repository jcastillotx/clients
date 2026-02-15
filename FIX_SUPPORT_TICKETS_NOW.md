# 🚨 ACTION REQUIRED: Apply Database Migration

## Your Support Tickets Feature Is Not Working

**Error**: `Could not find the table 'public.support_tickets' in the schema cache`

## Fix It Right Now (2 Minutes)

### Option 1: Automated Script (Easiest) ⭐

```bash
pnpm db:migrate
```

That's it! The script will handle everything.

### Option 2: Supabase Dashboard (If no local DB access)

1. Go to: https://app.supabase.com → Your Project → SQL Editor
2. Open: `lib/db/migrations/004_create_support_tickets_tables.sql`
3. Copy ALL contents
4. Paste in SQL Editor
5. Click "Run"

Done! ✅

## What This Does

Creates the missing database tables:
- `support_tickets` - Main tickets table
- `support_ticket_comments` - Comments on tickets

Plus indexes, security policies, and triggers.

## After Running

1. The error will disappear
2. Visit: http://localhost:3000/support
3. Page loads successfully!
4. You can create tickets

## Need More Details?

See: [URGENT_FIX_NEEDED.md](URGENT_FIX_NEEDED.md) for detailed instructions

## Prerequisites

You need database credentials in `.env.local`:

```bash
DATABASE_URL="postgresql://postgres:password@db.xxx.supabase.co:5432/postgres"
```

Get this from your Supabase Dashboard → Settings → Database

---

**Quick Fix**: `pnpm db:migrate`  
**That's all you need to do!** 🎯
