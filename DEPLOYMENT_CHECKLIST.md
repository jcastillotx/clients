# Deployment Checklist - Kre8iv Clients

## Status: Ready for Production Deployment 🚀

### ✅ Pre-Deployment Completed

- [x] All 55+ features implemented
- [x] Database schemas created (23 files)
- [x] 135 React components built
- [x] 63 API routes implemented
- [x] Enhanced libraries integrated
- [x] Vercel deployment fixed (npm configuration)
- [x] CLAUDE.md documentation created
- [x] .env.local template created

---

## Step 1: Environment Setup (15 minutes)

### 1.1 Create Supabase Project

```bash
# 1. Go to https://supabase.com
# 2. Click "New Project"
# 3. Fill in:
#    - Name: kre8iv-clients
#    - Database Password: [SAVE THIS SECURELY]
#    - Region: us-east-1 (or closest to you)
#    - Plan: Pro ($25/month for production)

# 4. Wait ~2 minutes for provisioning
```

### 1.2 Get Supabase Credentials

```bash
# In Supabase Dashboard:
# 1. Go to Settings → API
# 2. Copy these values to .env.local:

NEXT_PUBLIC_SUPABASE_URL=https://[YOUR-PROJECT].supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=[anon key from dashboard]
SUPABASE_SERVICE_KEY=[service_role key from dashboard]

# 3. Go to Settings → Database
# 4. Copy Connection String to .env.local:

DATABASE_URL=postgresql://postgres:[PASSWORD]@db.[YOUR-PROJECT].supabase.co:5432/postgres
```

### 1.3 Get Stripe Keys (for payments)

```bash
# 1. Go to https://dashboard.stripe.com
# 2. Get API keys from Developers → API keys
# 3. Add to .env.local:

NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...

# 4. Set up webhook:
# - Go to Developers → Webhooks
# - Add endpoint: https://your-domain.vercel.app/api/webhooks/stripe
# - Select events: payment_intent.succeeded, invoice.paid
# - Copy webhook secret:

STRIPE_WEBHOOK_SECRET=whsec_...
```

### 1.4 Get Resend API Key (for emails)

```bash
# 1. Go to https://resend.com
# 2. Create account and verify domain
# 3. Get API key from API Keys section
# 4. Add to .env.local:

RESEND_API_KEY=re_...
RESEND_FROM_EMAIL=noreply@yourdomain.com
```

### 1.5 Get Inngest Keys (for background jobs)

```bash
# 1. Go to https://inngest.com
# 2. Create account and project
# 3. Go to Settings → Keys
# 4. Add to .env.local:

INNGEST_EVENT_KEY=...
INNGEST_SIGNING_KEY=signkey-prod-...
NEXT_PUBLIC_INNGEST_APP_ID=your-app-id
```

---

## Step 2: Install Dependencies (2 minutes)

```bash
cd /Users/jlaptop/Apps/clients

# Install all dependencies
npm install

# Expected output:
# added 250+ packages in 45s
```

---

## Step 3: Database Setup (10-15 minutes)

### 3.1 Connect to Supabase

```bash
# Install Supabase CLI
npm install -g supabase

# Link to your project
supabase link --project-ref [YOUR-PROJECT-REF]

# Project ref is in your Supabase dashboard URL:
# https://app.supabase.com/project/[PROJECT-REF]
```

### 3.2 Run Migrations

```bash
# Go to migrations directory
cd lib/db/migrations

# Connect to database
psql $DATABASE_URL

# Run migrations in order:
\i 001_create_rbac_tables.sql
\i 002_create_template_tables.sql
\i 003_create_document_tables.sql
\i 010_feature_flags.sql

# Verify tables created
\dt

# Should see: rbac, templates, documents, features tables
```

### 3.3 Generate Remaining Schemas

```bash
# Back to project root
cd /Users/jlaptop/Apps/clients

# Generate migrations from Drizzle schemas
npm run db:generate

# Push to database
npm run db:push

# Open Drizzle Studio to verify
npm run db:studio
# Opens http://localhost:4983
```

### 3.4 Seed Feature Flags

```bash
# Connect to database
psql $DATABASE_URL

# Run seed file
\i lib/db/seeds/features-seed.sql

# Verify
SELECT COUNT(*) FROM features;
# Should return: 55 (all features seeded)
```

---

## Step 4: Local Testing (5 minutes)

```bash
# Start development server
npm run dev

# Open http://localhost:3000
# Should see login page

# Test checklist:
# [ ] Login page loads
# [ ] No console errors
# [ ] Supabase connection works
# [ ] Can access dashboard (after creating user)
```

### Create First User

```bash
# Option 1: Via Supabase Dashboard
# 1. Go to Authentication → Users
# 2. Click "Add user"
# 3. Email: admin@example.com
# 4. Password: [choose strong password]
# 5. Confirm email: [check box]

# Option 2: Via SQL
psql $DATABASE_URL

-- Create user in Supabase Auth
-- (Use Supabase dashboard for this)

-- Then add to users table
INSERT INTO users (id, email, name, is_active, status)
VALUES (
  '[user-uuid-from-supabase-auth]',
  'admin@example.com',
  'Admin User',
  true,
  'active'
);

-- Assign super admin role
INSERT INTO user_roles (user_id, role_id)
SELECT '[user-uuid]', id FROM roles WHERE name = 'super_admin';
```

---

## Step 5: Deploy to Vercel (10 minutes)

### 5.1 Push to GitHub

```bash
# Add all files
git add .

# Commit
git commit -m "Production ready - all features implemented, environment configured"

# Push
git push origin main
```

### 5.2 Deploy to Vercel

**Option A: Vercel Dashboard (Recommended)**

```bash
# 1. Go to https://vercel.com
# 2. Click "Add New Project"
# 3. Import from GitHub: jcastillotx/clients
# 4. Configure:
#    - Framework Preset: Next.js
#    - Root Directory: ./
#    - Build Command: npm run build
#    - Install Command: npm install --legacy-peer-deps

# 5. Add Environment Variables (copy from .env.local)
#    Click "Add" for each variable
#    CRITICAL: Add all REQUIRED variables from Step 1

# 6. Click "Deploy"
# 7. Wait 3-5 minutes for build
```

**Option B: Vercel CLI**

```bash
# Install Vercel CLI
npm install -g vercel

# Login
vercel login

# Deploy
vercel --prod

# Follow prompts:
# - Link to existing project? No
# - Project name: kre8iv-clients
# - Directory: ./

# Set environment variables
vercel env add NEXT_PUBLIC_SUPABASE_URL
# Paste value, press Enter
# Repeat for all environment variables

# Redeploy with env vars
vercel --prod
```

---

## Step 6: Post-Deployment Setup (10 minutes)

### 6.1 Configure Webhooks

```bash
# Stripe Webhook
# 1. Go to Stripe Dashboard → Webhooks
# 2. Add endpoint: https://[your-domain].vercel.app/api/webhooks/stripe
# 3. Copy webhook secret to Vercel env vars

# Inngest Endpoint
# 1. Go to Inngest Dashboard → Event Keys
# 2. Add webhook: https://[your-domain].vercel.app/api/inngest
```

### 6.2 Configure Supabase Auth Redirect

```bash
# 1. Go to Supabase Dashboard → Authentication → URL Configuration
# 2. Add Site URL: https://[your-domain].vercel.app
# 3. Add Redirect URLs:
#    - https://[your-domain].vercel.app/auth/callback
#    - https://[your-domain].vercel.app/dashboard
```

### 6.3 Enable RLS Policies

```bash
# Connect to production database
psql [PRODUCTION_DATABASE_URL]

# Run RLS policies
\i scripts/rls-policies.sql

# Verify RLS enabled
SELECT tablename, rowsecurity
FROM pg_tables
WHERE schemaname = 'public';

# All tables should have rowsecurity = true
```

---

## Step 7: Verification (15 minutes)

### 7.1 Smoke Tests

```bash
# Production URL: https://[your-domain].vercel.app

# Test checklist:
✅ Homepage loads
✅ Login works
✅ Dashboard loads
✅ Can create client
✅ Can create request
✅ Can create invoice
✅ File upload works
✅ No console errors
✅ Mobile responsive
✅ Fast page loads (<2s)
```

### 7.2 Feature Flags Test

```bash
# 1. Login as admin
# 2. Go to /admin/features
# 3. Test toggling features
# 4. Verify features appear/disappear for users
```

### 7.3 Payment Test

```bash
# 1. Create test invoice
# 2. Use Stripe test card: 4242 4242 4242 4242
# 3. Verify payment processes
# 4. Check Stripe dashboard for payment
```

---

## Step 8: Data Migration (Optional - if migrating from Laravel)

### 8.1 Export Laravel Data

```bash
# On Laravel server
mysqldump -u root -p kre8iv_clients > backup.sql

# Or use migration script
cd scripts
node migrate-data.ts
```

### 8.2 Transform and Import

```bash
# Run transformation
node scripts/migrate-data.ts

# Verify row counts
psql $DATABASE_URL

SELECT
  schemaname,
  tablename,
  n_live_tup as row_count
FROM pg_stat_user_tables
WHERE schemaname = 'public'
ORDER BY tablename;
```

---

## Step 9: Monitoring Setup (10 minutes)

### 9.1 Vercel Analytics

```bash
# Already included in package.json
# Automatically enabled on Vercel Pro plan
# View at: https://vercel.com/[project]/analytics
```

### 9.2 Sentry (Optional)

```bash
# 1. Go to https://sentry.io
# 2. Create Next.js project
# 3. Add to Vercel env vars:

SENTRY_DSN=https://...@sentry.io/...
SENTRY_AUTH_TOKEN=sntrys_...
NEXT_PUBLIC_SENTRY_ENVIRONMENT=production
```

### 9.3 Inngest Dashboard

```bash
# Monitor background jobs at:
# https://app.inngest.com/env/production/functions
```

---

## ✅ Success Criteria

**Deployment is successful when:**

- ✅ Application accessible at production URL
- ✅ Users can login
- ✅ All core features working
- ✅ No critical errors in logs
- ✅ Page load times < 2 seconds
- ✅ Payments processing (Stripe)
- ✅ Emails sending (Resend)
- ✅ Background jobs running (Inngest)
- ✅ File uploads working (Supabase Storage)
- ✅ Mobile responsive
- ✅ Feature flags working

---

## 🔧 Troubleshooting

### Build Fails on Vercel

```bash
# Check logs in Vercel Dashboard
# Common fixes:
1. Verify all env vars set
2. Check build command: npm run build
3. Verify install command: npm install --legacy-peer-deps
4. Check Node version: 20.x
```

### Database Connection Fails

```bash
# Verify DATABASE_URL format:
postgresql://postgres:[PASSWORD]@db.[PROJECT].supabase.co:5432/postgres

# Test connection:
psql $DATABASE_URL
```

### Stripe Webhook Fails

```bash
# 1. Verify webhook URL matches deployment
# 2. Check webhook secret in env vars
# 3. Test with Stripe CLI:
stripe listen --forward-to https://[domain]/api/webhooks/stripe
```

---

## 📊 Post-Launch

### Week 1

- Monitor error rates (Sentry)
- Check performance (Vercel Analytics)
- Gather user feedback
- Fix any critical bugs

### Week 2

- Enable optional features
- Migrate remaining data (if applicable)
- Optimize performance
- User training

### Month 1

- Review feature usage
- Optimize unused features
- Scale as needed
- Consider additional integrations

---

## 🎉 You're Live!

**Total deployment time: ~1-2 hours**

Your Laravel to Next.js migration is complete and deployed to production!

**Next Steps:**

1. Invite beta users
2. Monitor closely for 48 hours
3. Gather feedback
4. Iterate and improve

**Support:**

- Documentation: `/docs`
- CLAUDE.md: Project guide
- Issues: GitHub Issues
