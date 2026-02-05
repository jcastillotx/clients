# Production Testing Checklist

## Pre-Deployment Testing

### Authentication & Authorization

- [ ] **Login**
  - [ ] Login with email and password
  - [ ] Login with magic link
  - [ ] Incorrect password shows error
  - [ ] Remember me checkbox persists session
  - [ ] Logout clears session

- [ ] **Registration**
  - [ ] Create new account
  - [ ] Email verification sent
  - [ ] Email verification link works
  - [ ] Duplicate email shows error
  - [ ] Weak password rejected

- [ ] **Password Reset**
  - [ ] Request password reset
  - [ ] Reset email received
  - [ ] Reset link works
  - [ ] Password successfully changed
  - [ ] Can login with new password

- [ ] **Role-Based Access Control**
  - [ ] Admin can access admin panel
  - [ ] Client users see only their data
  - [ ] Staff can access assigned clients
  - [ ] Unauthorized users redirected

### Client Management

- [ ] **List Clients**
  - [ ] All clients displayed
  - [ ] Search filters work
  - [ ] Status filter works
  - [ ] Pagination works
  - [ ] Sort by name/date works

- [ ] **Create Client**
  - [ ] Form validation works
  - [ ] Client created successfully
  - [ ] Appears in list immediately
  - [ ] Email notification sent
  - [ ] Activity logged

- [ ] **Edit Client**
  - [ ] Form pre-filled with data
  - [ ] Updates save successfully
  - [ ] Changes reflected immediately
  - [ ] Activity logged

- [ ] **Delete Client**
  - [ ] Confirmation dialog shown
  - [ ] Soft delete (not hard delete)
  - [ ] Removed from active list
  - [ ] Activity logged

### Request Management

- [ ] **List Requests**
  - [ ] All requests displayed
  - [ ] Filter by status works
  - [ ] Filter by client works
  - [ ] Search works
  - [ ] Kanban board view works

- [ ] **Create Request**
  - [ ] Form validation works
  - [ ] Request number auto-generated
  - [ ] Client notification sent
  - [ ] Assigned staff notified
  - [ ] Activity logged

- [ ] **Update Request**
  - [ ] Status changes work
  - [ ] Comments added successfully
  - [ ] File attachments upload
  - [ ] SLA timer updates
  - [ ] Real-time updates work

- [ ] **SLA Monitoring**
  - [ ] Response SLA tracked
  - [ ] Resolution SLA tracked
  - [ ] Warnings sent at 80%
  - [ ] Breach notifications sent
  - [ ] Status updated correctly

### Invoice Management

- [ ] **List Invoices**
  - [ ] All invoices displayed
  - [ ] Filter by status works
  - [ ] Filter by client works
  - [ ] Search works

- [ ] **Create Invoice**
  - [ ] Invoice number auto-generated
  - [ ] Line items add/remove correctly
  - [ ] Totals calculate correctly
  - [ ] Tax calculated correctly
  - [ ] Discount applied correctly

- [ ] **Send Invoice**
  - [ ] Email sent to client
  - [ ] PDF attached correctly
  - [ ] Payment link works
  - [ ] Status updated to "sent"

- [ ] **Payment Processing**
  - [ ] Stripe payment form loads
  - [ ] Test card payment succeeds
  - [ ] Webhook updates invoice status
  - [ ] Confirmation email sent
  - [ ] Activity logged

- [ ] **Recurring Invoices**
  - [ ] Recurring invoice created
  - [ ] Next generation date calculated
  - [ ] Auto-generated on schedule
  - [ ] Client notified

### Document Management

- [ ] **Upload Document**
  - [ ] Drag-and-drop works
  - [ ] File upload succeeds
  - [ ] Progress bar shows
  - [ ] Tags added correctly
  - [ ] Appears in list immediately

- [ ] **Download Document**
  - [ ] Signed URL generated
  - [ ] Download starts automatically
  - [ ] Original filename preserved
  - [ ] Access logged

- [ ] **Document Versions**
  - [ ] New version uploads
  - [ ] Version number increments
  - [ ] Parent/child relationship created
  - [ ] Latest version marked correctly

- [ ] **Document Sharing**
  - [ ] Share dialog works
  - [ ] Permissions set correctly
  - [ ] Expiration date works
  - [ ] Access count tracked

### Contract Management

- [ ] **List Contracts**
  - [ ] All contracts displayed
  - [ ] Filter by status works
  - [ ] Filter by client works
  - [ ] Search works

- [ ] **Create Contract**
  - [ ] Contract number auto-generated
  - [ ] All fields save correctly
  - [ ] Auto-renewal settings work
  - [ ] Document attachment works

- [ ] **Contract Lifecycle**
  - [ ] Status changes correctly
  - [ ] Expiration warnings sent (30 days)
  - [ ] Urgent warnings sent (7 days)
  - [ ] Auto-renewal works
  - [ ] Expired contracts marked

### Admin Panel

- [ ] **Dashboard**
  - [ ] All statistics load
  - [ ] Top clients calculated correctly
  - [ ] SLA breaches displayed
  - [ ] Recent activity shown
  - [ ] Charts render correctly

- [ ] **User Management**
  - [ ] All users listed
  - [ ] Search works
  - [ ] Create user works
  - [ ] Edit user works
  - [ ] Role assignment works
  - [ ] Delete user works (soft delete)

- [ ] **System Settings**
  - [ ] Email templates editable
  - [ ] Invoice templates editable
  - [ ] Settings save correctly

### Background Jobs

- [ ] **Invoice Reminders**
  - [ ] Daily schedule triggered
  - [ ] Due soon emails sent
  - [ ] Overdue emails sent
  - [ ] Reminder timestamps updated

- [ ] **Recurring Invoice Generation**
  - [ ] Daily schedule triggered
  - [ ] Invoices generated correctly
  - [ ] Line items copied
  - [ ] Next generation date updated

- [ ] **SLA Checks**
  - [ ] 5-minute schedule triggered
  - [ ] Warnings sent correctly
  - [ ] Breaches detected
  - [ ] Notifications sent

- [ ] **Contract Checks**
  - [ ] Daily schedule triggered
  - [ ] Expiration notices sent
  - [ ] Contracts expired
  - [ ] Auto-renewals processed

### Email System

- [ ] **Template Rendering**
  - [ ] Variables substituted correctly
  - [ ] Loops work (invoice line items)
  - [ ] HTML rendered correctly
  - [ ] Plain text fallback generated

- [ ] **Email Delivery**
  - [ ] Emails sent successfully
  - [ ] From address correct
  - [ ] Reply-to address correct
  - [ ] Attachments included
  - [ ] Links work correctly

### Payment Processing

- [ ] **Stripe Integration**
  - [ ] Payment form loads
  - [ ] Test card works (4242 4242 4242 4242)
  - [ ] Payment succeeds
  - [ ] Failed payment handled
  - [ ] Refund processed correctly

- [ ] **Webhook Handling**
  - [ ] payment_intent.succeeded processed
  - [ ] payment_intent.payment_failed processed
  - [ ] charge.refunded processed
  - [ ] Subscription events processed

## Performance Testing

### Page Load Times

- [ ] **Homepage**: < 2 seconds
- [ ] **Dashboard**: < 2 seconds
- [ ] **Request List**: < 2 seconds
- [ ] **Invoice List**: < 2 seconds
- [ ] **Document List**: < 2 seconds

### Database Query Performance

- [ ] No N+1 queries
- [ ] Complex queries < 100ms
- [ ] List queries paginated
- [ ] Indexes on foreign keys
- [ ] Indexes on frequently queried columns

### Asset Optimization

- [ ] Images optimized
- [ ] JavaScript minified
- [ ] CSS minified
- [ ] Fonts loaded correctly
- [ ] No console errors

## Security Testing

### Authentication

- [ ] Brute force protection
- [ ] Session timeout works
- [ ] CSRF protection enabled
- [ ] XSS protection works
- [ ] SQL injection prevented

### Authorization

- [ ] RLS policies enforced
- [ ] API routes permission-gated
- [ ] Direct URL access blocked
- [ ] Role escalation prevented

### Data Protection

- [ ] Passwords hashed (bcrypt)
- [ ] Sensitive data encrypted
- [ ] HTTPS enforced
- [ ] Secure headers set
- [ ] CORS configured correctly

### File Upload Security

- [ ] File type validation
- [ ] File size limits enforced
- [ ] Malicious files rejected
- [ ] Files scanned (if applicable)

## Accessibility Testing

### Screen Reader Compatibility

- [ ] Headings hierarchical
- [ ] ARIA labels present
- [ ] Focus indicators visible
- [ ] Alt text on images
- [ ] Form labels associated

### Keyboard Navigation

- [ ] All interactive elements accessible
- [ ] Tab order logical
- [ ] Skip navigation links work
- [ ] Modal trapping works
- [ ] Escape closes dialogs

### Color Contrast

- [ ] Text contrast meets WCAG AA
- [ ] Interactive elements visible
- [ ] Error states clear
- [ ] Success states clear

## Mobile Responsiveness

### Viewport Sizes

- [ ] **Mobile (375px)**
  - [ ] Navigation works
  - [ ] Forms usable
  - [ ] Tables scroll horizontally
  - [ ] Buttons accessible

- [ ] **Tablet (768px)**
  - [ ] Layout adapts correctly
  - [ ] Navigation collapses
  - [ ] Grids responsive

- [ ] **Desktop (1024px+)**
  - [ ] Full layout displays
  - [ ] All features accessible

## Browser Compatibility

- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Safari (iOS)
- [ ] Chrome Mobile (Android)

## Error Handling

### User-Friendly Errors

- [ ] Form validation errors clear
- [ ] Network errors handled gracefully
- [ ] Server errors show friendly message
- [ ] Retry mechanisms work
- [ ] Error boundaries catch crashes

### Logging

- [ ] Errors logged to Sentry
- [ ] Activity logged to database
- [ ] Background job failures logged
- [ ] Webhook failures logged

## Monitoring

### Vercel Analytics

- [ ] Page views tracked
- [ ] Web Vitals measured
- [ ] User journeys visible

### Inngest Dashboard

- [ ] Functions executing
- [ ] Success rates visible
- [ ] Retry attempts shown
- [ ] Performance metrics displayed

### Supabase Logs

- [ ] Database queries logged
- [ ] Authentication events logged
- [ ] Storage operations logged

## User Acceptance Testing

### Client User Journey

1. [ ] Receive invitation email
2. [ ] Set password
3. [ ] Login
4. [ ] View dashboard
5. [ ] Submit request
6. [ ] Upload document
7. [ ] View invoice
8. [ ] Pay invoice
9. [ ] Receive confirmation

### Staff User Journey

1. [ ] Login
2. [ ] View assigned requests
3. [ ] Update request status
4. [ ] Add comments
5. [ ] Create invoice
6. [ ] Send invoice
7. [ ] Upload contract
8. [ ] Generate report

### Admin User Journey

1. [ ] Login
2. [ ] View admin dashboard
3. [ ] Create new user
4. [ ] Assign roles
5. [ ] Edit email template
6. [ ] View system logs
7. [ ] Monitor background jobs

## Post-Deployment Verification

### Immediate (First Hour)

- [ ] All critical pages load
- [ ] Login works
- [ ] Payment processing works
- [ ] Email sending works
- [ ] Background jobs running

### First 24 Hours

- [ ] No critical errors in Sentry
- [ ] Background jobs executing on schedule
- [ ] Email delivery rate > 95%
- [ ] Payment success rate > 95%
- [ ] User feedback collected

### First Week

- [ ] Performance metrics stable
- [ ] Error rate < 1%
- [ ] User retention > 90%
- [ ] Support tickets < 10/day
- [ ] System uptime > 99.9%

## Known Issues & Workarounds

Document any known issues discovered during testing:

1. **Issue**: [Description]
   - **Impact**: [High/Medium/Low]
   - **Workaround**: [Steps to work around]
   - **Fix ETA**: [Date or version]

## Sign-off

- [ ] **Developer**: Testing complete, all critical paths verified
- [ ] **QA**: Acceptance criteria met, ready for production
- [ ] **Product Owner**: Feature parity confirmed, user stories validated
- [ ] **DevOps**: Infrastructure ready, monitoring configured
- [ ] **Security**: Security audit passed, vulnerabilities addressed
