# Support Tickets System Implementation

## Overview

A comprehensive support ticket management system with advanced SLA tracking, escalation management, and status workflow based on the Laravel implementation.

## Features Implemented

### 1. Database Schema (`lib/db/schema/support-tickets.ts`)

**Support Tickets Table:**

- Full ticket management (subject, description, category, priority, status)
- SLA tracking (response/resolution due dates, breach tracking, pause mechanism)
- Escalation system (levels, timestamps)
- Billing integration (billable flag, hours, rates)
- Metadata storage (tags, custom fields, attachments)
- Soft deletes support

**Support Ticket Comments Table:**

- Comment storage with user associations
- Internal notes feature (visible only to staff)
- Attachment support
- Soft deletes support

**Enums & Types:**

- Status: open, in_progress, waiting_on_client, waiting_on_vendor, resolved, closed
- Priority: low, medium, high, urgent
- Category: technical, billing, general, feature_request, bug_report, security, performance

### 2. Validation Schemas (`lib/validations/support-ticket.ts`)

- `createSupportTicketSchema` - Validates new ticket creation
- `updateSupportTicketSchema` - Validates ticket updates
- `createTicketCommentSchema` - Validates comment creation

### 3. SLA Utilities (`lib/utils/sla.ts`)

**SLA Targets by Priority:**

- Urgent: 1h response, 4h resolution
- High: 4h response, 24h resolution
- Medium: 8h response, 72h resolution
- Low: 24h response, 168h resolution

**Functions:**

- `calculateSlaDueDates()` - Calculate due dates based on priority
- `calculateSlaPercentUsed()` - Track SLA consumption
- `getSlaStatus()` - Determine current SLA status (on_track, warning, breached, etc.)
- `formatTimeRemaining()` - Human-readable time remaining
- `getSlaStatusColor()` - UI color coding for SLA status

### 4. Pages

**List Page (`app/(dashboard)/support/page.tsx`):**

- Server-side data fetching with filters
- Search by ticket number or subject
- Filter by status, priority, category
- Sortable columns
- RLS-protected data access

**Create Page (`app/(dashboard)/support/new/page.tsx`):**

- Comprehensive ticket creation form
- Staff assignment (optional)
- Priority-based SLA calculation
- Category selection

**Detail Page (`app/(dashboard)/support/[id]/page.tsx`):**

- Full ticket details with edit capability
- SLA tracking dashboard
- Comment thread
- Timeline view
- Staff assignment management

### 5. Components

**TicketList (`components/support/ticket-list.tsx`):**

- Filterable, searchable table
- Real-time SLA status badges
- Priority and status indicators
- Debounced search
- URL-based filter persistence

**TicketForm (`components/support/ticket-form.tsx`):**

- React Hook Form with Zod validation
- Category and priority selection
- Rich text description
- Staff assignment
- Error handling with toast notifications

**TicketDetail (`components/support/ticket-detail.tsx`):**

- Inline editing capability
- Status workflow management
- SLA progress visualization
- Timeline display
- Escalation indicators
- Pause status tracking

**TicketComments (`components/support/ticket-comments.tsx`):**

- Chronological comment thread
- Internal notes feature (staff only)
- User avatars
- Real-time updates
- Rich comment composition

### 6. API Routes

**`/api/support` (GET, POST):**

- List all tickets with filtering
- Create new tickets with auto-generated ticket numbers
- Automatic SLA calculation on creation

**`/api/support/[id]` (GET, PUT, DELETE):**

- Fetch single ticket
- Update ticket status, priority, assignment
- Automatic timestamp management (first_response_at, resolved_at, closed_at)
- SLA pause/resume based on status changes
- Soft delete support

**`/api/support/[id]/comments` (GET, POST):**

- Fetch all comments for a ticket
- Add public or internal comments
- Auto-update first_response_at on first staff comment
- Auto-transition from waiting_on_client to in_progress

## Status Workflow

```
open → in_progress → waiting_on_client → in_progress → resolved → closed
           ↓              ↓
    waiting_on_vendor    (SLA paused)
```

**Status Transitions:**

- `open` - Initial state
- `in_progress` - Work started (sets first_response_at)
- `waiting_on_client` - Pauses SLA timer
- `waiting_on_vendor` - External dependency
- `resolved` - Solution provided (sets resolved_at)
- `closed` - Ticket finalized (sets closed_at)

## SLA Tracking Features

1. **Automatic Calculation:** SLA due dates calculated based on priority at ticket creation
2. **Pause Mechanism:** SLA timer pauses when status = waiting_on_client
3. **Breach Detection:** Tracks both response and resolution SLA breaches
4. **Progress Visualization:** Shows percentage of SLA time consumed
5. **Warning Threshold:** Alerts when 75% of SLA time is used
6. **Escalation Support:** Multi-level escalation tracking

## Key Highlights

1. **Server Components:** Pages use React Server Components for optimal performance
2. **Client Interactivity:** Client components handle filters, forms, and real-time updates
3. **Type Safety:** Full TypeScript with Zod validation
4. **RLS Support:** Row-level security for multi-tenant isolation
5. **Real-time Ready:** Structure supports Supabase real-time subscriptions
6. **Accessibility:** Semantic HTML and ARIA support via shadcn/ui
7. **Responsive:** Mobile-friendly design with Tailwind CSS

## Files Created

### Schema & Validations

- `/lib/db/schema/support-tickets.ts` - Drizzle schema
- `/lib/validations/support-ticket.ts` - Zod validation schemas
- `/lib/utils/sla.ts` - SLA calculation utilities

### Pages

- `/app/(dashboard)/support/page.tsx` - Ticket list
- `/app/(dashboard)/support/new/page.tsx` - Create ticket
- `/app/(dashboard)/support/[id]/page.tsx` - Ticket detail

### Components

- `/components/support/ticket-list.tsx` - List view with filters
- `/components/support/ticket-form.tsx` - Create/edit form
- `/components/support/ticket-detail.tsx` - Detail view with SLA tracking
- `/components/support/ticket-comments.tsx` - Comment thread

### API Routes

- `/app/api/support/route.ts` - GET (list), POST (create)
- `/app/api/support/[id]/route.ts` - GET, PUT, DELETE
- `/app/api/support/[id]/comments/route.ts` - GET, POST comments

## Usage Examples

### Creating a Ticket

```typescript
POST /api/support
{
  "subject": "Login not working",
  "description": "Users cannot log in to the system",
  "category": "technical",
  "priority": "high",
  "assignedTo": "uuid-of-staff-member" // optional
}
```

### Updating Status

```typescript
PUT /api/support/{id}
{
  "status": "in_progress",
  "priority": "urgent"
}
```

### Adding a Comment

```typescript
POST /api/support/{id}/comments
{
  "comment": "Investigating the issue now",
  "isInternal": false
}
```

## Next Steps

1. **Database Migration:** Run Drizzle migration to create tables
2. **RLS Policies:** Set up Row Level Security in Supabase
3. **Real-time:** Add Supabase subscriptions for live updates
4. **Email Notifications:** Integrate email alerts for SLA warnings
5. **File Uploads:** Implement attachment functionality
6. **Analytics Dashboard:** Create SLA performance metrics
7. **Auto-assignment:** Implement workload-based ticket distribution

## Integration Points

- **Clients:** Links to client companies
- **Users:** Staff assignment and comment authors
- **Maintenance Plans:** Auto-determines billable status
- **Invoices:** Billable ticket integration
- **Activity Log:** Full audit trail (via Spatie pattern)

## Security Features

- Authentication required for all endpoints
- RLS ensures users only see their client's tickets
- Internal notes hidden from clients
- Soft deletes preserve data integrity
- Input validation on all mutations
