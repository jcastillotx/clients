# Migration Verification Checklist

**Complete this checklist before considering the migration successful**

## Database Verification

### Data Integrity

- [ ] **Row counts match** between MySQL and PostgreSQL for all tables

  ```sql
  -- Run on MySQL
  SELECT table_name, table_rows
  FROM information_schema.tables
  WHERE table_schema = 'kre8iv_clients';

  -- Run on PostgreSQL
  SELECT schemaname, tablename, n_live_tup as row_count
  FROM pg_stat_user_tables
  WHERE schemaname = 'public';
  ```

- [ ] **Sample data spot checks** - Verify 10 random records per table match exactly
- [ ] **Foreign key relationships** preserved - All joins work correctly
- [ ] **NULL values** handled correctly - No unexpected NULLs
- [ ] **JSON/JSONB data** parsed correctly - enabled_features, custom_fields, etc.
- [ ] **Dates/timestamps** converted correctly - Timezone awareness verified
- [ ] **Boolean fields** converted correctly - No 0/1 instead of true/false

### Schema Verification

- [ ] **All enums created** and used (user_status, client_status, request_status, etc.)
- [ ] **Indexes created** - All GIN, BTREE, and composite indexes exist
- [ ] **Constraints active** - CHECK, UNIQUE, NOT NULL constraints enforced
- [ ] **Triggers working** - updated_at triggers fire correctly
- [ ] **Functions created** - Helper functions (get_user_client_id, is_super_admin, etc.)

### RLS Policies

- [ ] **Policies enabled** on all client-scoped tables
- [ ] **Client isolation** working - Users can only see their client's data
- [ ] **Staff access** working - Staff can see assigned clients
- [ ] **Super admin access** working - Super admins can see everything
- [ ] **Permission checks** working - user_has_permission() function correct

### Performance

- [ ] **Query performance** acceptable - Key queries <100ms
- [ ] **Full-text search** working - SEO keywords, knowledge base, requests
- [ ] **JSONB queries** performant - GIN indexes used
- [ ] **Join performance** good - Composite indexes helping

---

## Authentication & Authorization

### Supabase Auth

- [ ] **All users migrated** - User count matches
- [ ] **Login works** - Email/password authentication functional
- [ ] **Email verification** status preserved
- [ ] **User metadata** correct - name, phone, avatar, client_id, etc.
- [ ] **2FA setup** - Users can enable TOTP (must re-enable after migration)
- [ ] **Password reset** flow works
- [ ] **Magic links** work (if enabled)

### RBAC System

- [ ] **Roles created** - admin, manager, staff, client_user, etc.
- [ ] **Permissions created** - requests.create, invoices.update, contracts.update, etc.
- [ ] **Role assignments** migrated - user_roles table populated
- [ ] **Permission checks** working - hasPermission() function correct
- [ ] **Manual permissions** respected - JSON array in users.manual_permissions

### Session Management

- [ ] **Login persists** across page refreshes
- [ ] **Logout works** correctly
- [ ] **Session timeout** appropriate (configurable)
- [ ] **Concurrent sessions** handled (if allowed)

---

## Core Features

### Client Management

- [ ] **Client list** loads correctly
- [ ] **Client detail** shows all information
- [ ] **Create client** works with validation
- [ ] **Update client** saves changes
- [ ] **Delete client** soft deletes (if applicable)
- [ ] **Staff assignments** work - assign/remove staff from clients
- [ ] **Client contacts** CRUD operations work
- [ ] **Client notes** CRUD operations work
- [ ] **Activity logs** record client changes

### Service Requests

- [ ] **Request list** shows all user's requests
- [ ] **Filtering** works - by status, assigned user, date range
- [ ] **Sorting** works - by created_at, due_date, priority
- [ ] **Search** works - full-text search on title/description
- [ ] **Create request** with all fields
- [ ] **Update request** status, assigned user, due date
- [ ] **Comments** - add, edit, delete comments
- [ ] **File attachments** - upload, download, delete
- [ ] **Real-time updates** - changes appear immediately
- [ ] **Notifications** - users notified of mentions, assignments

### Invoicing

- [ ] **Invoice list** shows all client invoices
- [ ] **Invoice detail** displays all items
- [ ] **Create invoice** with multiple line items
- [ ] **Update invoice** items, amounts, due dates
- [ ] **Send invoice** email delivery works
- [ ] **Record payment** updates status correctly
- [ ] **Partial payments** tracked
- [ ] **Overdue invoices** flagged correctly
- [ ] **Recurring invoices** generated automatically
- [ ] **Invoice reminders** sent on schedule
- [ ] **PDF generation** creates proper invoice PDFs
- [ ] **Stripe integration** - Payment Intents work
- [ ] **Webhook handling** - Stripe webhooks processed

### Contracts

- [ ] **Contract list** loads
- [ ] **Contract detail** shows all fields
- [ ] **Create contract** with templates
- [ ] **Update contract** terms, dates
- [ ] **E-signature flow** works end-to-end
- [ ] **Signed contracts** marked correctly
- [ ] **Contract expiration** detected
- [ ] **Auto-renewal** works (if enabled)
- [ ] **PDF generation** creates contract PDFs

### Documents

- [ ] **Document library** displays all documents
- [ ] **Upload document** - small files (<5MB)
- [ ] **Upload large file** - resumable uploads work (100MB+)
- [ ] **Download document** - signed URLs work
- [ ] **Document versioning** - upload new version
- [ ] **Delete document** - removes from storage
- [ ] **Folder organization** - create/move folders
- [ ] **Document sharing** - generate share links
- [ ] **RLS enforcement** - users can't access other clients' docs
- [ ] **Storage buckets** - documents, contracts, invoices, avatars

### Projects & Tasks

- [ ] **Project list** shows all projects
- [ ] **Project detail** with tasks
- [ ] **Create project** with members
- [ ] **Update project** status, dates, budget
- [ ] **Project members** - add/remove members
- [ ] **Task list** filters and sorts correctly
- [ ] **Create task** with assignments
- [ ] **Update task** status, assignee, due date
- [ ] **Task comments** work
- [ ] **Task dependencies** enforced (if implemented)

### Messaging (Real-time Chat)

- [ ] **Conversation list** loads
- [ ] **Message history** displays correctly
- [ ] **Send message** works
- [ ] **Real-time updates** - new messages appear immediately
- [ ] **Read receipts** update correctly
- [ ] **Typing indicators** show (if implemented)
- [ ] **Unread count** accurate
- [ ] **Notifications** for new messages

---

## Background Jobs & Automation

### Inngest Jobs

- [ ] **Invoice reminders** run on schedule (daily 9am)
  - [ ] 7-day reminder sent
  - [ ] 3-day reminder sent
  - [ ] 1-day reminder sent
  - [ ] Overdue reminder sent

- [ ] **Recurring invoices** generated correctly (daily)
- [ ] **SLA checks** run every 5 minutes
- [ ] **Brand monitoring** runs on schedule (hourly, 6 hours, 30 min)
- [ ] **Analytics reports** generated (daily, weekly, monthly, quarterly)
- [ ] **Storage sync** runs every 5 minutes
- [ ] **Audit log purging** runs daily
- [ ] **Failed jobs** retry correctly
- [ ] **Job logs** captured in Inngest dashboard

### Automation Engine

- [ ] **Automation rules** execute when triggered
- [ ] **Conditions** evaluated correctly
- [ ] **Actions** performed as configured
- [ ] **Rule logs** captured
- [ ] **Rule enable/disable** works

### Webhooks

- [ ] **Outgoing webhooks** fire on events
- [ ] **Retry logic** works on failures
- [ ] **Webhook signatures** valid
- [ ] **Webhook logs** captured
- [ ] **Stripe webhooks** processed correctly

---

## Third-Party Integrations

### Stripe

- [ ] **Payment Intents** create successfully
- [ ] **Customer creation** works
- [ ] **Payment method** attachment works
- [ ] **3D Secure** authentication flow works
- [ ] **Webhooks** received and processed
- [ ] **Subscription management** works (if applicable)
- [ ] **Invoice items** sync correctly

### Email (Resend)

- [ ] **Transactional emails** delivered
- [ ] **Email templates** render correctly
- [ ] **Attachments** work (invoice PDFs)
- [ ] **Bounce handling** works
- [ ] **Unsubscribe links** work

### AI Providers

- [ ] **OpenAI API** calls work
- [ ] **Anthropic API** calls work
- [ ] **Google Gemini API** calls work
- [ ] **Document analysis** works
- [ ] **Request triage** works
- [ ] **Cost tracking** accurate

### Storage (Supabase Storage)

- [ ] **File uploads** work
- [ ] **File downloads** work
- [ ] **Signed URLs** generated correctly
- [ ] **Image transformations** work (resize, compress)
- [ ] **RLS policies** enforced on buckets

---

## Frontend (Next.js)

### Performance

- [ ] **First Contentful Paint** <1.5s
- [ ] **Largest Contentful Paint** <2.5s
- [ ] **Time to Interactive** <3s
- [ ] **Cumulative Layout Shift** <0.1
- [ ] **API response times** p95 <300ms
- [ ] **Server Components** reduce bundle size vs. Livewire
- [ ] **Code splitting** working - only load what's needed
- [ ] **Lazy loading** implemented for heavy components
- [ ] **Image optimization** working (Next.js Image)

### UI/UX

- [ ] **No Bootstrap+Tailwind conflicts** - Pure Tailwind only
- [ ] **Consistent design** - shadcn/ui components throughout
- [ ] **Responsive design** - works on mobile, tablet, desktop
- [ ] **Touch targets** appropriate size (min 44x44px on mobile)
- [ ] **Loading states** clear and consistent
- [ ] **Error states** handled gracefully
- [ ] **Success feedback** provided after actions
- [ ] **Form validation** real-time with helpful messages

### Accessibility

- [ ] **WCAG 2.1 AA** compliance verified
- [ ] **Keyboard navigation** works everywhere
- [ ] **Focus indicators** visible
- [ ] **Screen reader** compatibility tested
- [ ] **ARIA labels** on interactive elements
- [ ] **Color contrast** sufficient (4.5:1 minimum)
- [ ] **Skip links** present
- [ ] **Form labels** associated correctly

### Browser Compatibility

- [ ] **Chrome** (latest)
- [ ] **Firefox** (latest)
- [ ] **Safari** (latest)
- [ ] **Edge** (latest)
- [ ] **Mobile Safari** (iOS 15+)
- [ ] **Chrome Mobile** (Android)

---

## Security

### Authentication Security

- [ ] **Passwords** hashed securely (bcrypt)
- [ ] **Session tokens** stored securely (httpOnly cookies)
- [ ] **CSRF protection** enabled
- [ ] **Rate limiting** on login attempts
- [ ] **2FA** available and working
- [ ] **Password reset** secure (token expiration)

### Authorization Security

- [ ] **RLS policies** prevent unauthorized data access
- [ ] **API routes** check permissions
- [ ] **Client-side** doesn't expose unauthorized data
- [ ] **Super admin** protection works (can't delete self)
- [ ] **Staff assignments** respected everywhere

### Data Security

- [ ] **Database** encrypted at rest
- [ ] **Connections** encrypted in transit (TLS 1.3)
- [ ] **Sensitive data** not logged
- [ ] **Environment variables** not committed to Git
- [ ] **API keys** stored securely

### Input Validation

- [ ] **All forms** validate with Zod schemas
- [ ] **SQL injection** prevented (parameterized queries)
- [ ] **XSS** prevented (React escaping + DOMPurify)
- [ ] **File uploads** validated (type, size, content)
- [ ] **Rate limiting** on all API routes

---

## Monitoring & Observability

### Error Tracking

- [ ] **Sentry** capturing errors
- [ ] **Source maps** uploaded
- [ ] **Breadcrumbs** showing user actions before error
- [ ] **User feedback** widget working
- [ ] **Error grouping** logical
- [ ] **Alerts** configured for critical errors

### Performance Monitoring

- [ ] **Vercel Analytics** tracking Core Web Vitals
- [ ] **Real User Monitoring** enabled
- [ ] **Server response times** tracked
- [ ] **Database query times** logged
- [ ] **API route performance** monitored

### Business Metrics

- [ ] **User signups** tracked
- [ ] **Invoice creation** tracked
- [ ] **Payment conversions** tracked
- [ ] **Document uploads** tracked
- [ ] **Request creation** tracked
- [ ] **Custom dashboards** showing KPIs

---

## Deployment

### Vercel

- [ ] **Production deployment** successful
- [ ] **Environment variables** configured correctly
- [ ] **Edge Functions** working
- [ ] **Serverless Functions** not timing out
- [ ] **Domain** configured correctly (DNS)
- [ ] **SSL certificate** valid
- [ ] **Preview deployments** working for PRs

### Supabase

- [ ] **Database** provisioned (Pro plan)
- [ ] **Daily backups** enabled
- [ ] **Point-in-time recovery** available
- [ ] **Connection pooling** configured (PgBouncer)
- [ ] **Storage buckets** configured with RLS
- [ ] **Edge Functions** deployed (if using)

### CI/CD

- [ ] **GitHub Actions** running on push
- [ ] **Linting** passes
- [ ] **Type checking** passes
- [ ] **Unit tests** pass
- [ ] **E2E tests** pass
- [ ] **Build** succeeds
- [ ] **Deployment** automatic on merge to main

---

## Testing

### Unit Tests (Vitest)

- [ ] **Auth utilities** tested
- [ ] **Form validation** tested
- [ ] **Data transformations** tested
- [ ] **Helper functions** tested
- [ ] **Coverage** >80%

### Integration Tests (Playwright)

- [ ] **Login flow** tested
- [ ] **Create request** flow tested
- [ ] **Invoice payment** flow tested
- [ ] **Document upload** flow tested
- [ ] **Contract signing** flow tested
- [ ] **Mobile viewport** tested

### Load Testing

- [ ] **Concurrent users** tested (100, 500, 1000)
- [ ] **Database** handles load
- [ ] **API routes** respond quickly under load
- [ ] **Background jobs** don't fall behind

---

## Documentation

- [ ] **API documentation** complete (TypeDoc)
- [ ] **Component Storybook** published
- [ ] **Migration guide** for developers
- [ ] **User guide** for end users
- [ ] **Admin guide** for administrators
- [ ] **Runbooks** for common operations
- [ ] **Incident response** procedures documented

---

## Final Sign-Off

### Development Team

- [ ] Lead Developer: All features working
- [ ] Backend Developer: Database migration verified
- [ ] Frontend Developer: UI/UX polished
- [ ] QA Engineer: All tests passing

### Business Team

- [ ] Product Owner: Feature parity confirmed
- [ ] CEO: Business continuity acceptable
- [ ] Support Manager: Support team trained

### Deployment Checklist

- [ ] Rollback plan tested and ready
- [ ] Monitoring alerts configured
- [ ] Status page prepared
- [ ] Communication templates ready
- [ ] Emergency contacts documented
- [ ] Post-launch support scheduled (48-hour monitoring)

---

## Post-Launch Monitoring (First 48 Hours)

### Hour 1-4

- [ ] Monitor error rates (Sentry dashboard)
- [ ] Check Core Web Vitals (Vercel Analytics)
- [ ] Verify background jobs running (Inngest dashboard)
- [ ] Review user login success rate
- [ ] Check payment processing (Stripe dashboard)

### Hour 4-24

- [ ] Review all alerts triggered
- [ ] Spot check user-reported issues
- [ ] Verify data sync continuing correctly
- [ ] Check database connection pool usage
- [ ] Monitor server resource utilization

### Hour 24-48

- [ ] Generate migration success report
- [ ] Analyze performance vs. Laravel baseline
- [ ] Review customer feedback
- [ ] Plan post-launch improvements
- [ ] Schedule retrospective meeting

---

**Migration Status**: [ ] Not Started / [ ] In Progress / [ ] **Complete**

**Sign-off Date**: ******\_\_\_******
**Lead Developer**: ******\_\_\_******
**Product Owner**: ******\_\_\_******
