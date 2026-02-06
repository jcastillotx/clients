# Week-by-Week Migration Breakdown

**Detailed weekly tasks with deliverables and verification**

## Pre-Week 1: Preparation (1-2 days)

### Infrastructure Setup

- [ ] Create Supabase project (Pro plan)
- [ ] Set up Vercel account and connect GitHub repo
- [ ] Configure environment variables
- [ ] Set up Sentry project for error tracking
- [ ] Set up Inngest account
- [ ] Configure Resend for email
- [ ] Set up Stripe test environment

### Team Onboarding

- [ ] Review migration plan with entire team
- [ ] Assign roles and responsibilities
- [ ] Set up Slack channel: #laravel-to-nextjs-migration
- [ ] Create GitHub project board
- [ ] Schedule daily standups (15 min)

**Deliverable**: Infrastructure provisioned, team ready

---

## Week 1: Database Migration (Part 1)

### Day 1-2: Schema Conversion

- [ ] Export MySQL schema: `mysqldump --no-data`
- [ ] Run pgloader for initial conversion
- [ ] Create PostgreSQL enums (user_status, client_status, etc.)
- [ ] Convert JSON → JSONB columns
- [ ] Add GIN indexes for JSONB columns
- [ ] Create full-text search indexes

**Scripts to run**:

```bash
# Export MySQL schema
mysqldump -u root -p --no-data kre8iv_clients > backups/schema.sql

# Initial conversion with pgloader
pgloader mysql://user:pass@localhost/kre8iv_clients \
          postgresql://postgres:pass@db.xxx.supabase.co/postgres

# Apply refinements
psql -h db.xxx.supabase.co -U postgres -d postgres \
  -f migration/scripts/convert-schema.sql
```

### Day 3-4: RLS Policies

- [ ] Enable RLS on all client-scoped tables
- [ ] Create helper functions (auth.user_id(), auth.user_client_id(), etc.)
- [ ] Create policies for clients table
- [ ] Create policies for users table
- [ ] Create policies for requests, invoices, contracts, documents
- [ ] Test RLS policies with sample queries

**Scripts to run**:

```bash
psql -h db.xxx.supabase.co -U postgres -d postgres \
  -f migration/scripts/rls-policies.sql
```

### Day 5: Verification

- [ ] Run schema verification queries
- [ ] Test RLS policies as different user types
- [ ] Check index creation
- [ ] Verify constraint enforcement
- [ ] Document any schema discrepancies

**Deliverable**: PostgreSQL schema ready with RLS policies

---

## Week 2: Database Migration (Part 2) & Auth

### Day 1-2: User Migration

- [ ] Run user migration script
- [ ] Verify bcrypt passwords transferred
- [ ] Check email_verified status preserved
- [ ] Verify user metadata (client_id, roles, etc.)
- [ ] Test login with migrated accounts
- [ ] Confirm 2FA users identified (must re-enable)

**Scripts to run**:

```bash
cd migration/scripts
pnpm install
tsx migrate-users.ts
```

### Day 3-4: Data Migration

- [ ] Run data migration script (full dataset)
- [ ] Monitor progress (batch inserts)
- [ ] Verify foreign key relationships preserved
- [ ] Check row counts match MySQL
- [ ] Spot-check sample data in each table
- [ ] Validate polymorphic relationships

**Scripts to run**:

```bash
tsx migrate-data.ts
```

### Day 5: RBAC Setup

- [ ] Create roles table and seed data
- [ ] Create permissions table and seed data
- [ ] Migrate role_permissions mappings
- [ ] Migrate user_roles assignments
- [ ] Test permission checking functions
- [ ] Verify super admin protections

**Deliverable**: All data migrated, authentication working

---

## Week 3: Next.js Foundation & Client Management

### Day 1: Next.js Setup

- [ ] Initialize Next.js 15 project
  ```bash
  pnpx create-next-app@latest kre8iv-clients-nextjs \
    --typescript --tailwind --app --import-alias "@/*"
  ```
- [ ] Install dependencies (Drizzle, shadcn/ui, TanStack Query)
- [ ] Configure Tailwind with brand colors
- [ ] Set up Drizzle ORM with schema files
- [ ] Configure Supabase client/server helpers

### Day 2: shadcn/ui Setup

- [ ] Initialize shadcn/ui
  ```bash
  npx shadcn-ui@latest init
  ```
- [ ] Install core components:
  ```bash
  npx shadcn-ui@latest add button card input select table \
    dialog sheet form textarea badge avatar
  ```
- [ ] Create custom theme variants
- [ ] Set up dark mode toggle (if applicable)

### Day 3-4: Client Management Pages

- [ ] Create layout system: `app/(dashboard)/layout.tsx`
- [ ] Implement client list page (Server Component)
- [ ] Implement client detail page
- [ ] Create client form component (React Hook Form + Zod)
- [ ] Add staff assignment UI
- [ ] Implement client contacts CRUD
- [ ] Add client notes functionality

### Day 5: Testing & Polish

- [ ] Write unit tests for client components
- [ ] Test RLS enforcement (users can't see other clients)
- [ ] Mobile responsiveness check
- [ ] Accessibility audit with axe DevTools
- [ ] Code review

**Deliverable**: Client management fully functional

---

## Week 4: Service Requests

### Day 1-2: Request Pages

- [ ] Implement request list (with filtering, sorting, search)
- [ ] Implement request detail page
- [ ] Create request form (Zod validation)
- [ ] Add request status updates
- [ ] Implement assigned user selection

### Day 3: Comments & Attachments

- [ ] Implement comment system
- [ ] Add file upload for attachments
- [ ] Configure Supabase Storage bucket (private)
- [ ] Implement file download (signed URLs)
- [ ] Add file delete functionality

### Day 4: Real-time & Kanban

- [ ] Implement Supabase Realtime for request updates
- [ ] Add real-time comment notifications
- [ ] Create Kanban board view (optional)
- [ ] Implement drag-and-drop for status changes

### Day 5: Testing

- [ ] E2E tests for request creation flow
- [ ] Test file upload/download
- [ ] Test real-time updates with 2+ users
- [ ] Performance testing (100+ requests)

**Deliverable**: Request management complete with real-time

---

## Week 5: Invoicing & Payments

### Day 1-2: Invoice Pages

- [ ] Implement invoice list (with filters)
- [ ] Implement invoice detail page
- [ ] Create invoice form with line items
- [ ] Add invoice status management
- [ ] Implement recurring invoice UI

### Day 3: Stripe Integration

- [ ] Set up Stripe SDK
- [ ] Implement Payment Intent creation
- [ ] Create payment form (Stripe Elements)
- [ ] Handle 3D Secure authentication
- [ ] Implement webhook handler
- [ ] Add payment confirmation UI

### Day 4: PDF Generation

- [ ] Install @react-pdf/renderer
- [ ] Create invoice PDF template
- [ ] Implement PDF generation API route
- [ ] Add "Download PDF" button
- [ ] Test PDF formatting

### Day 5: Testing & Polish

- [ ] Test Stripe payment flow end-to-end
- [ ] Test webhook handling (use Stripe CLI)
- [ ] Test recurring invoice generation
- [ ] Verify invoice reminder system ready for Inngest

**Deliverable**: Invoicing + Stripe payments working

---

## Week 6: Documents & Contracts

### Day 1-2: Document Library

- [ ] Set up Supabase Storage buckets (documents, contracts)
- [ ] Configure RLS policies on buckets
- [ ] Implement document upload (with progress bar)
- [ ] Add resumable uploads for large files (100MB+)
- [ ] Implement document list with folders
- [ ] Add document search

### Day 3: Document Management

- [ ] Implement document versioning
- [ ] Add document download (signed URLs)
- [ ] Implement document delete
- [ ] Create document sharing (generate share links)
- [ ] Add document metadata editing

### Day 4: Contracts

- [ ] Implement contract list and detail pages
- [ ] Create contract form
- [ ] Implement e-signature flow (API integration or custom)
- [ ] Add contract PDF generation
- [ ] Implement contract expiration tracking

### Day 5: Testing

- [ ] Test file upload for various sizes (1KB to 100MB)
- [ ] Test RLS on storage buckets
- [ ] Test document versioning
- [ ] Verify e-signature flow
- [ ] Mobile file upload testing

**Deliverable**: Documents + Contracts functional

---

## Week 7: Background Jobs & Automation

### Day 1-2: Inngest Setup

- [ ] Set up Inngest project
- [ ] Configure Inngest API keys
- [ ] Create invoice reminder functions
  - 7-day reminder
  - 3-day reminder
  - 1-day reminder
  - Overdue reminder
- [ ] Create recurring invoice generation function
- [ ] Set up Inngest dev server for testing

### Day 3: Scheduled Tasks

- [ ] Migrate SLA check job
- [ ] Migrate brand monitoring jobs
- [ ] Migrate analytics report generation
- [ ] Migrate storage sync job
- [ ] Migrate audit log purging job
- [ ] Configure Vercel Cron for triggers

### Day 4: Automation Engine

- [ ] Implement automation rules CRUD pages
- [ ] Create rule condition builder UI
- [ ] Create action configuration UI
- [ ] Implement rule execution engine
- [ ] Add rule logging

### Day 5: Webhooks

- [ ] Implement outgoing webhook system
- [ ] Create webhook CRUD pages
- [ ] Add webhook signature verification
- [ ] Implement retry logic
- [ ] Add webhook logs

**Deliverable**: Background jobs + Automation working

---

## Week 8: Admin Panel & Testing

### Day 1-2: Admin Dashboard

- [ ] Create admin layout (separate from dashboard)
- [ ] Implement admin dashboard with KPIs
- [ ] Create user management pages
- [ ] Implement role/permission management UI
- [ ] Add system settings pages

### Day 3: AI Features

- [ ] Integrate Vercel AI SDK
- [ ] Implement document analysis feature
- [ ] Implement request triage with AI
- [ ] Add AI provider configuration UI
- [ ] Implement cost tracking

### Day 4: Comprehensive Testing

- [ ] Run full test suite (Vitest + Playwright)
- [ ] Load testing (k6 or similar)
- [ ] Security scanning (npm audit, Snyk)
- [ ] Accessibility audit (WCAG 2.1 AA)
- [ ] Performance testing (Lighthouse)

### Day 5: Bug Fixes & Polish

- [ ] Fix issues found in testing
- [ ] Mobile responsiveness final checks
- [ ] Cross-browser testing
- [ ] User acceptance testing (UAT)

**Deliverable**: Complete application tested

---

## Week 9-10: Deployment & Launch

### Week 9 Day 1-2: Pre-Launch Prep

- [ ] Final data sync from MySQL to PostgreSQL
- [ ] Run full verification checklist
- [ ] Practice rollback procedure
- [ ] Prepare monitoring dashboards
- [ ] Configure production environment variables
- [ ] Set up production Inngest webhooks

### Week 9 Day 3-4: Staging Deployment

- [ ] Deploy to Vercel staging environment
- [ ] Run smoke tests on staging
- [ ] Test with production data snapshot
- [ ] Share staging link with stakeholders for UAT
- [ ] Fix any issues found

### Week 9 Day 5: Go/No-Go Meeting

- [ ] Review verification checklist
- [ ] Review rollback plan
- [ ] Confirm monitoring ready
- [ ] Get stakeholder sign-off
- [ ] Schedule launch window

### Week 10 Day 1: Launch Day

**Morning** (6am-12pm):

- [ ] Final backup of Laravel MySQL database
- [ ] Deploy Next.js to production
- [ ] Update DNS to point to Vercel
- [ ] Monitor closely for first 4 hours
- [ ] Verify key user flows working

**Afternoon** (12pm-6pm):

- [ ] Continue monitoring
- [ ] Respond to any user-reported issues
- [ ] Check background jobs running
- [ ] Verify payment processing
- [ ] Review Sentry error logs

**Evening** (6pm-11pm):

- [ ] Generate launch day report
- [ ] Email users confirming successful migration
- [ ] Continue monitoring

### Week 10 Day 2: Post-Launch Day 1

- [ ] 24-hour health check
- [ ] Review all monitoring dashboards
- [ ] Address any overnight issues
- [ ] Check Core Web Vitals performance
- [ ] Verify data sync continuing correctly

### Week 10 Day 3-5: Stabilization

- [ ] Continue monitoring (reduced intensity)
- [ ] Fix minor bugs as they arise
- [ ] Gather user feedback
- [ ] Performance optimization based on real usage
- [ ] Generate migration success report

---

## Post-Launch: Week 11+

### Week 11: Retrospective & Optimization

- [ ] Hold team retrospective meeting
- [ ] Document lessons learned
- [ ] Identify performance optimization opportunities
- [ ] Plan technical debt paydown
- [ ] Celebrate success! 🎉

### Week 12+: Ongoing Improvements

- [ ] Monitor metrics vs. Laravel baseline
- [ ] Implement user feedback
- [ ] Continue accessibility improvements
- [ ] Add features that were deferred
- [ ] Decommission Laravel infrastructure

---

## Daily Standup Format

**3 Questions (5 min each person)**:

1. What did you complete yesterday?
2. What are you working on today?
3. Any blockers?

**Example**:

> **Backend Dev 1**:
>
> - Yesterday: Completed RLS policies for requests and invoices tables
> - Today: Working on document storage RLS policies
> - Blockers: Need clarification on polymorphic relationship handling
>
> **Full-Stack Dev 1**:
>
> - Yesterday: Implemented client list page with Server Components
> - Today: Building client detail page and form
> - Blockers: None

---

## Weekly Status Report Template

**Week [Number]: [Week Name]**

### Accomplishments

- ✅ [Completed item 1]
- ✅ [Completed item 2]

### In Progress

- 🔄 [Item being worked on]
- 🔄 [Item being worked on]

### Blockers

- ⚠️ [Blocker description and mitigation plan]

### Next Week Plan

- [ ] [Planned item 1]
- [ ] [Planned item 2]

### Risks & Concerns

- [Any concerns or risks identified]

### Metrics

- Lines of code migrated: [Number]
- Tests written: [Number]
- Components built: [Number]

---

**Total Duration**: 10 weeks (8 weeks implementation + 2 weeks deployment/stabilization)

**Team Commitment**: 4-5 developers full-time

**Budget**: Infrastructure costs ~$71/month after migration complete
