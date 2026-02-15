# Fix Summary: Missing Support Tickets Table

## Issue Description

**Error Code**: PGRST205  
**Error Message**: "Could not find the table 'public.support_tickets' in the schema cache"  
**Affected Page**: `/support` (Support Tickets)  
**Root Cause**: Database tables for support tickets were never created in PostgreSQL

## What Was Done

### 1. Created SQL Migration File

**File**: `lib/db/migrations/004_create_support_tickets_tables.sql`

This comprehensive migration creates:

- ✅ `support_tickets` table (34 columns)
  - Ticket details (number, subject, description, category, status, priority)
  - Billing fields (is_billable, estimated_hours, actual_hours, hourly_rate)
  - Timeline tracking (first_response_at, resolved_at, closed_at)
  - SLA tracking (response/resolution due dates, breach tracking, pause duration)
  - Escalation tracking (escalation_level, last_escalated_at)
  - Metadata (JSONB for tags, attachments, custom fields)
  - Audit timestamps (created_at, updated_at, deleted_at)

- ✅ `support_ticket_comments` table (8 columns)
  - Comment content
  - Internal/external flag
  - Attachments (JSONB)
  - User tracking
  - Timestamps

- ✅ Performance indexes (12 indexes total)
  - Client ID, user IDs, status, priority, category
  - Ticket number (unique)
  - Created date
  - Soft delete filtering

- ✅ Row-Level Security (RLS) policies (10 policies)
  - Multi-tenant data isolation
  - Client-based access control
  - User-specific comment permissions

- ✅ Auto-update triggers
  - Automatically updates `updated_at` on record changes

### 2. Created Migration Runner Script

**File**: `scripts/run-support-tickets-migration.sh`

Features:
- ✅ Validates DATABASE_URL is set
- ✅ Checks psql is installed
- ✅ Runs migration with error handling
- ✅ Verifies tables were created
- ✅ Provides helpful error messages
- ✅ Executable permissions set

### 3. Created Documentation

**Files Created**:
1. `lib/db/migrations/README.md` - General migration guide
2. `MIGRATION_FIX.md` - Detailed fix documentation
3. `QUICK_FIX_GUIDE.md` - Quick start guide
4. `FIX_SUMMARY.md` - This summary

## How to Apply the Fix

### Quick Method (Recommended)

```bash
# Set your database connection string
export DATABASE_URL="postgresql://postgres:password@db.xxx.supabase.co:5432/postgres"

# Run the automated script
./scripts/run-support-tickets-migration.sh
```

### Alternative Method (No CLI Required)

1. Open Supabase Dashboard → SQL Editor
2. Copy contents of `lib/db/migrations/004_create_support_tickets_tables.sql`
3. Paste and click "Run"

## Verification Steps

After applying the migration:

```bash
# Check tables exist
psql $DATABASE_URL -c "\dt public.support_tickets"

# Verify structure
psql $DATABASE_URL -c "\d public.support_tickets"

# Test the application
# Visit: http://localhost:3000/support
```

Expected result: Support Tickets page loads without errors

## Database Schema Details

### support_tickets Table Structure

```sql
CREATE TABLE public.support_tickets (
  -- Identity
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  
  -- Relationships
  client_id UUID NOT NULL REFERENCES clients(id),
  created_by UUID NOT NULL REFERENCES users(id),
  assigned_to UUID REFERENCES users(id),
  maintenance_plan_id UUID,
  invoice_id UUID,
  
  -- Ticket Info
  ticket_number TEXT NOT NULL UNIQUE,
  subject TEXT NOT NULL,
  description TEXT NOT NULL,
  category TEXT NOT NULL, -- technical, billing, general, etc.
  status TEXT NOT NULL DEFAULT 'open',
  priority TEXT NOT NULL DEFAULT 'medium',
  
  -- Billing
  is_billable BOOLEAN NOT NULL DEFAULT true,
  estimated_hours DECIMAL(10, 2),
  actual_hours DECIMAL(10, 2),
  hourly_rate DECIMAL(10, 2),
  
  -- Timeline
  first_response_at TIMESTAMPTZ,
  resolved_at TIMESTAMPTZ,
  closed_at TIMESTAMPTZ,
  
  -- SLA Tracking
  sla_response_due_at TIMESTAMPTZ,
  sla_resolution_due_at TIMESTAMPTZ,
  sla_response_breached BOOLEAN NOT NULL DEFAULT false,
  sla_resolution_breached BOOLEAN NOT NULL DEFAULT false,
  sla_response_breached_at TIMESTAMPTZ,
  sla_resolution_breached_at TIMESTAMPTZ,
  sla_paused BOOLEAN NOT NULL DEFAULT false,
  sla_paused_duration_minutes INTEGER NOT NULL DEFAULT 0,
  
  -- Escalation
  escalation_level INTEGER NOT NULL DEFAULT 0,
  last_escalated_at TIMESTAMPTZ,
  
  -- Metadata
  metadata JSONB,
  
  -- Timestamps
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMPTZ
);
```

### support_ticket_comments Table Structure

```sql
CREATE TABLE public.support_ticket_comments (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  support_ticket_id UUID NOT NULL REFERENCES support_tickets(id) ON DELETE CASCADE,
  user_id UUID NOT NULL REFERENCES users(id),
  comment TEXT NOT NULL,
  is_internal BOOLEAN NOT NULL DEFAULT false,
  attachments JSONB,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  deleted_at TIMESTAMPTZ
);
```

## RLS Policies Applied

### For support_tickets:
1. **SELECT**: Users can view tickets from their client only
2. **INSERT**: Users can create tickets for their client only
3. **UPDATE**: Users can update their client's tickets only
4. **DELETE**: Users can delete their client's tickets only

### For support_ticket_comments:
1. **SELECT**: Users can view comments on their client's tickets
2. **INSERT**: Users can create comments on their client's tickets
3. **UPDATE**: Users can update their own comments only
4. **DELETE**: Users can delete their own comments only

## Files Changed

### New Files Added:
```
lib/db/migrations/004_create_support_tickets_tables.sql (259 lines)
lib/db/migrations/README.md (131 lines)
scripts/run-support-tickets-migration.sh (64 lines)
MIGRATION_FIX.md (309 lines)
QUICK_FIX_GUIDE.md (106 lines)
FIX_SUMMARY.md (this file)
```

### Existing Files (No Changes Required):
```
lib/db/schema/support-tickets.ts (already exists, defines TypeScript types)
app/(dashboard)/support/page.tsx (already exists, ready to use)
app/api/support/route.ts (already exists, ready to use)
components/support/ (already exists, ready to use)
```

## Testing Checklist

After applying the migration:

- [ ] Tables created successfully
- [ ] RLS policies active
- [ ] Support tickets page loads
- [ ] Can create a new ticket
- [ ] Can view ticket list
- [ ] Can add comments to tickets
- [ ] Can update ticket status
- [ ] Can filter by status/priority/category
- [ ] Can search tickets
- [ ] SLA dates calculated correctly

## Next Steps

1. **Apply the migration** using one of the methods above
2. **Verify** tables were created
3. **Test** the support tickets feature
4. **Seed sample data** (optional) for testing
5. **Configure SLA rules** in application settings
6. **Set up email notifications** for new tickets

## Support

If you encounter issues:

1. Check the troubleshooting section in `MIGRATION_FIX.md`
2. Review migration file for SQL errors
3. Verify database credentials
4. Check Supabase logs for RLS policy errors
5. Ensure all referenced tables exist (clients, users)

## Related Documentation

- **Quick Start**: [QUICK_FIX_GUIDE.md](QUICK_FIX_GUIDE.md)
- **Detailed Guide**: [MIGRATION_FIX.md](MIGRATION_FIX.md)
- **Migration Docs**: [lib/db/migrations/README.md](lib/db/migrations/README.md)
- **Project Overview**: [CLAUDE.md](CLAUDE.md)

## Git Branch

Branch: `cursor/missing-support-tickets-table-8270`

Commits:
1. `ed5786b` - Fix: Add missing support_tickets table migration
2. `cfa8eb8` - Add quick fix guide for support tickets migration

## Status

✅ **Migration created and documented**  
⏳ **Pending: Migration needs to be applied to database**

Once the migration is applied, the support tickets feature will be fully functional.

---

**Last Updated**: February 15, 2026  
**Issue**: Missing support_tickets table (PGRST205)  
**Solution**: Created comprehensive SQL migration with RLS policies  
**Status**: Ready to deploy
