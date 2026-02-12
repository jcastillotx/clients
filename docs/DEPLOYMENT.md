# Production Deployment Guide

## Overview

This guide covers deploying the Kre8iv Clients platform to production using:
- **Vercel** - Next.js hosting
- **Supabase** - PostgreSQL + Auth + Storage
- **Inngest Cloud** - Background jobs
- **Resend** - Transactional emails
- **Stripe** - Payment processing

## Prerequisites

### Accounts Required

1. **Vercel**: https://vercel.com (free for hobby projects)
2. **Supabase**: https://supabase.com (free tier available)
3. **Inngest**: https://app.inngest.com (free tier: 10k steps/month)
4. **Resend**: https://resend.com (free tier: 100 emails/day)
5. **Stripe**: https://stripe.com (production account)

### Domain Setup

1. Purchase domain or use existing
2. Configure DNS to point to Vercel
3. Verify domain in Resend for email sending

## Part 1: Supabase Production Setup

### 1.1 Create Production Project

```bash
# Visit https://app.supabase.com
# Click "New Project"
# Name: kre8iv-clients-prod
# Region: Choose closest to your users
# Database Password: Generate strong password (save securely!)
```

### 1.2 Run Database Migrations

```bash
# Install Supabase CLI
npm install -g supabase

# Link to production project
supabase link --project-ref YOUR_PROJECT_REF

# Run migrations
cd lib/db/migrations
supabase db push
```

### 1.3 Configure Authentication

**Email Templates:**
1. Go to Authentication > Email Templates
2. Customize templates for:
   - Confirm signup
   - Magic Link
   - Change Email Address
   - Reset Password

**Auth Settings:**
```
Site URL: https://yourdomain.com
Redirect URLs:
  - https://yourdomain.com/auth/callback
  - https://yourdomain.com/dashboard

Email Auth: Enabled
Password Requirements: 
  - Minimum 8 characters
  - Require lowercase
  - Require uppercase
  - Require numbers

Session Timeout: 7 days
```

### 1.4 Configure Storage

**Create Buckets:**
1. Go to Storage
2. Create buckets:
   - `documents` (Private, RLS enabled)
   - `contracts` (Private, RLS enabled)
   - `invoices` (Private, RLS enabled)
   - `avatars` (Public)

**CORS Configuration:**
```json
{
  "allowedOrigins": ["https://yourdomain.com"],
  "allowedMethods": ["GET", "POST", "PUT", "DELETE"],
  "allowedHeaders": ["*"],
  "maxAge": 3600
}
```

### 1.5 Enable Row Level Security

All tables should already have RLS policies from migrations. Verify:

```sql
-- Check RLS is enabled
SELECT tablename, rowsecurity 
FROM pg_tables 
WHERE schemaname = 'public';

-- All tables should show rowsecurity = true
```

### 1.6 Get Connection Details

Save these for environment variables:

```
Project URL: https://YOUR_PROJECT_REF.supabase.co
Anon Key: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Service Role Key: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9... (KEEP SECRET!)
Database URL: postgresql://postgres:[PASSWORD]@db.YOUR_PROJECT_REF.supabase.co:5432/postgres
```

## Part 2: Vercel Deployment

### 2.1 Connect Repository

```bash
# Install Vercel CLI
npm i -g vercel

# Login
vercel login

# Deploy from repository root
cd /path/to/nextjs-app
vercel

# Follow prompts:
# - Link to existing project? No
# - Project name: kre8iv-clients
# - Which directory? ./
# - Override settings? No
```

### 2.2 Configure Environment Variables

In Vercel Dashboard (Settings > Environment Variables):

**Supabase:**
```bash
NEXT_PUBLIC_SUPABASE_URL=https://YOUR_PROJECT_REF.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=your_anon_key
SUPABASE_SERVICE_ROLE_KEY=your_service_role_key
```

**App Configuration:**
```bash
NEXT_PUBLIC_APP_URL=https://yourdomain.com
NEXT_PUBLIC_SITE_URL=https://yourdomain.com
NODE_ENV=production
```

Use your actual user-facing domain here (not the `*.vercel.app` preview domain) so Supabase email links resolve to the same host where users requested them.

**Inngest:**
```bash
INNGEST_EVENT_KEY=your_inngest_event_key
INNGEST_SIGNING_KEY=your_signing_key
```

**Email (Resend):**
```bash
RESEND_API_KEY=re_your_api_key
EMAIL_FROM=noreply@yourdomain.com
EMAIL_REPLY_TO=support@yourdomain.com
```

**Stripe:**
```bash
STRIPE_SECRET_KEY=sk_live_...
STRIPE_PUBLISHABLE_KEY=pk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=pk_live_...
```

### 2.3 Deploy to Production

```bash
# Deploy to production
vercel --prod

# Or from Vercel Dashboard:
# Go to Deployments > Production > Deploy
```

### 2.4 Configure Custom Domain

```bash
# Add domain in Vercel Dashboard
vercel domains add yourdomain.com

# Configure DNS:
# Type: CNAME
# Name: @ (or subdomain)
# Value: cname.vercel-dns.com
```

### 2.5 Configure Supabase Auth Redirect URLs

In Supabase Dashboard (Authentication > URL Configuration):

- **Site URL**: `https://yourdomain.com`
- **Additional Redirect URLs** should include at least:
  - `https://yourdomain.com/auth/confirm`
  - `https://yourdomain.com/reset-password`

If these point to a `*.vercel.app` URL while users are on a custom domain, email/magic-link verification can fail.

## Part 3: Inngest Cloud Setup

### 3.1 Create Project

1. Visit https://app.inngest.com
2. Create new app: "kre8iv-clients"
3. Note the Event Key and Signing Key

### 3.2 Register Functions

Inngest will auto-discover functions from:
```
https://yourdomain.com/api/inngest
```

**Verify Registration:**
1. Check Inngest Dashboard > Functions
2. Should see:
   - send-invoice-reminders
   - generate-recurring-invoices
   - check-sla-compliance
   - check-contract-expirations

### 3.3 Test Functions

```bash
# Trigger test run from Inngest Dashboard
# Go to Functions > [function-name] > Test
```

## Part 4: Stripe Configuration

### 4.1 Activate Production Mode

1. Complete business verification
2. Activate live payments
3. Set up bank account for payouts

### 4.2 Configure Webhooks

```
Endpoint URL: https://yourdomain.com/api/webhooks/stripe
Events to send:
  - payment_intent.succeeded
  - payment_intent.payment_failed
  - charge.refunded
  - customer.subscription.created
  - customer.subscription.updated
  - customer.subscription.deleted

Webhook Signing Secret: Save as STRIPE_WEBHOOK_SECRET
```

### 4.3 Test Webhook

```bash
# Install Stripe CLI
brew install stripe/stripe-cli/stripe

# Login
stripe login

# Test webhook
stripe trigger payment_intent.succeeded
```

## Part 5: Resend Email Setup

### 5.1 Verify Domain

1. Go to Resend Dashboard > Domains
2. Add domain: yourdomain.com
3. Add DNS records (shown in Resend):
   - TXT record for verification
   - MX records for receiving
   - DKIM records for authentication

### 5.2 Create API Key

1. Go to API Keys > Create
2. Name: "Production - Kre8iv Clients"
3. Permissions: Full access
4. Save as RESEND_API_KEY

### 5.3 Test Emails

```bash
# From Vercel Functions or Inngest
curl -X POST https://yourdomain.com/api/test-email \
  -H "Content-Type: application/json" \
  -d '{"to":"test@example.com"}'
```

## Part 6: Monitoring & Observability

### 6.1 Vercel Analytics

```bash
# Already installed via @vercel/analytics
# View at: https://vercel.com/dashboard/analytics
```

Metrics tracked:
- Page views
- Unique visitors
- Top pages
- Web Vitals (LCP, FID, CLS)

### 6.2 Sentry Error Tracking

```bash
# Install Sentry
npm install @sentry/nextjs

# Initialize
npx @sentry/wizard -i nextjs
```

**Configure:**
```javascript
// sentry.client.config.ts
import * as Sentry from "@sentry/nextjs";

Sentry.init({
  dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,
  environment: process.env.NODE_ENV,
  tracesSampleRate: 1.0,
});
```

### 6.3 Supabase Logs

Monitor in Supabase Dashboard:
- Database > Logs
- Authentication > Logs
- Storage > Logs
- Edge Functions > Logs

### 6.4 Inngest Dashboard

Monitor at https://app.inngest.com:
- Function execution history
- Success/failure rates
- Retry attempts
- Performance metrics

## Part 7: Security Checklist

### 7.1 Environment Variables

- [ ] All secrets in environment variables (not in code)
- [ ] Service role keys never exposed to client
- [ ] Webhook secrets configured correctly
- [ ] API keys rotated regularly

### 7.2 Supabase Security

- [ ] RLS enabled on all tables
- [ ] RLS policies tested for all user roles
- [ ] Service role only used server-side
- [ ] Database backups enabled (automatic in Supabase)
- [ ] SSL enforced for all connections

### 7.3 Application Security

- [ ] HTTPS enforced (automatic on Vercel)
- [ ] Secure headers configured
- [ ] CORS properly configured
- [ ] Rate limiting enabled (Vercel Edge Config)
- [ ] Input validation on all forms
- [ ] XSS protection via React's auto-escaping
- [ ] SQL injection prevented via Supabase client

### 7.4 Authentication Security

- [ ] Strong password requirements
- [ ] Email verification required
- [ ] Session timeout configured
- [ ] 2FA available (optional, via Supabase)
- [ ] Password reset flow tested

## Part 8: Performance Optimization

### 8.1 Next.js Configuration

```javascript
// next.config.js
module.exports = {
  images: {
    domains: ['YOUR_PROJECT_REF.supabase.co'],
    formats: ['image/avif', 'image/webp'],
  },
  compiler: {
    removeConsole: process.env.NODE_ENV === 'production',
  },
};
```

### 8.2 Database Optimization

```sql
-- Create indexes for common queries
CREATE INDEX CONCURRENTLY idx_requests_status 
  ON requests(status) 
  WHERE deleted_at IS NULL;

CREATE INDEX CONCURRENTLY idx_invoices_status 
  ON invoices(status) 
  WHERE deleted_at IS NULL;

CREATE INDEX CONCURRENTLY idx_contracts_end_date 
  ON contracts(end_date) 
  WHERE status = 'active';

-- Analyze query performance
EXPLAIN ANALYZE 
SELECT * FROM requests 
WHERE status = 'pending' 
  AND deleted_at IS NULL;
```

### 8.3 Caching Strategy

**Vercel Edge Caching:**
```typescript
// app/api/some-route/route.ts
export const revalidate = 60; // Cache for 60 seconds
```

**React Query (Client-side):**
```typescript
const { data } = useQuery({
  queryKey: ['requests'],
  queryFn: fetchRequests,
  staleTime: 5 * 60 * 1000, // 5 minutes
});
```

## Part 9: Launch Checklist

### Pre-Launch

- [ ] All migrations run successfully
- [ ] RLS policies verified
- [ ] User roles and permissions tested
- [ ] Email templates configured
- [ ] Stripe webhooks working
- [ ] Inngest functions scheduled correctly
- [ ] Environment variables set correctly
- [ ] Custom domain configured
- [ ] SSL certificate active
- [ ] Analytics tracking verified
- [ ] Error tracking configured

### Launch Day

- [ ] Final production deploy
- [ ] Smoke test all critical paths
- [ ] Verify email sending
- [ ] Test payment flow end-to-end
- [ ] Verify background jobs running
- [ ] Monitor error logs
- [ ] Check performance metrics
- [ ] Notify users of migration

### Post-Launch (First 48 Hours)

- [ ] Monitor Sentry for errors
- [ ] Check Vercel Analytics for traffic
- [ ] Verify Inngest jobs executing
- [ ] Monitor Supabase logs
- [ ] Check email delivery rates
- [ ] Verify payment processing
- [ ] Test user workflows
- [ ] Collect user feedback

## Part 10: Rollback Plan

### If Critical Issues Arise

**1. Immediate Rollback (Vercel):**
```bash
# From Vercel Dashboard:
# Go to Deployments > [Previous Stable] > Promote to Production
```

**2. Database Rollback:**
```bash
# Run previous migration down
supabase db reset
```

**3. Revert DNS:**
```
# Point domain back to Laravel server
# Type: A Record
# Value: [Laravel Server IP]
```

**4. Communication:**
- Post incident report
- Notify users of status
- Document lessons learned

## Support Resources

- **Vercel Support**: https://vercel.com/support
- **Supabase Support**: https://supabase.com/support
- **Inngest Support**: https://www.inngest.com/support
- **Stripe Support**: https://support.stripe.com

## Maintenance

### Regular Tasks

**Daily:**
- Monitor error logs (Sentry)
- Check background job execution (Inngest)
- Review email delivery rates (Resend)

**Weekly:**
- Review performance metrics (Vercel Analytics)
- Check database backups (Supabase)
- Update dependencies (security patches)

**Monthly:**
- Review and rotate API keys
- Analyze user growth and usage
- Optimize slow queries
- Update documentation
