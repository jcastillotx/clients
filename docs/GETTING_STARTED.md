# Getting Started with the Migration

**Quick start guide for beginning the Laravel to Next.js migration**

## ✅ What's Already Done

### Planning & Documentation

- ✅ Complete migration plan (8-10 weeks, 9 tasks)
- ✅ Tech stack selected and documented
- ✅ Database schema conversion scripts ready
- ✅ RLS security policies written
- ✅ Rollback plan prepared
- ✅ Verification checklist created
- ✅ Before/after code examples documented

### Next.js Application Foundation

- ✅ Project structure created (`/migration/nextjs-app/`)
- ✅ package.json with all dependencies
- ✅ TypeScript configuration
- ✅ Tailwind CSS setup with brand colors
- ✅ Supabase client/server helpers
- ✅ Authentication middleware
- ✅ Drizzle ORM schemas (users, clients, requests)
- ✅ Zod validation schemas
- ✅ Example API route
- ✅ Example Server Component page
- ✅ Example Client Component
- ✅ Custom hooks (useDebounce)

## 🚀 Next Steps to Begin Migration

### Step 1: Review the Plan (30 minutes)

```bash
cd /Users/jlaptop/Apps/clients/migration

# Read the main documents
cat README.md
cat TECH_STACK.md
cat WEEKLY_BREAKDOWN.md
```

**Key Questions to Answer:**

- Do you agree with the tech stack choices?
- Is the 8-10 week timeline acceptable?
- Do you have budget for $71/month infrastructure?
- Can you assemble a 4-5 person team?

### Step 2: Provision Infrastructure (1-2 hours)

#### 2a. Create Supabase Project

1. Go to https://supabase.com
2. Click "New Project"
3. Choose:
   - Name: `kre8iv-clients`
   - Database Password: (save securely)
   - Region: Same as Vercel (for low latency)
   - Plan: **Pro** ($25/month) - required for production

4. Wait for project to provision (~2 minutes)

5. Save credentials:
   ```bash
   NEXT_PUBLIC_SUPABASE_URL=https://xxx.supabase.co
   NEXT_PUBLIC_SUPABASE_ANON_KEY=eyJhbGc...
   SUPABASE_SERVICE_KEY=eyJhbGc... (from Settings → API)
   ```

#### 2b. Set Up Vercel

1. Go to https://vercel.com
2. Import GitHub repository
3. Configure build settings:
   - Framework Preset: Next.js
   - Root Directory: `migration/nextjs-app`
   - Build Command: `pnpm build`
   - Install Command: `pnpm install`

4. Add environment variables (from `.env.example`)

#### 2c. Set Up Additional Services

**Stripe (Payments)**:

1. Go to https://stripe.com
2. Create account
3. Get API keys from Dashboard → Developers
4. Save:
   ```bash
   NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY=pk_test_...
   STRIPE_SECRET_KEY=sk_test_...
   ```

**Resend (Email)**:

1. Go to https://resend.com
2. Create account
3. Add domain
4. Get API key
5. Save:
   ```bash
   RESEND_API_KEY=re_...
   ```

**Inngest (Background Jobs)**:

1. Go to https://inngest.com
2. Create account
3. Create project
4. Get keys from Settings
5. Save:
   ```bash
   INNGEST_EVENT_KEY=...
   INNGEST_SIGNING_KEY=...
   ```

**Sentry (Error Tracking)**:

1. Go to https://sentry.io
2. Create Next.js project
3. Get DSN from project settings
4. Save:
   ```bash
   SENTRY_DSN=https://...@sentry.io/...
   ```

### Step 3: Run Week 1 - Database Migration (5 days)

#### Day 1-2: Schema Conversion

```bash
# 1. Export MySQL schema
mysqldump -u root -p --no-data kre8iv_clients > migration/backups/schema.sql

# 2. Run pgloader for initial conversion
# Install pgloader first: brew install pgloader (macOS)
pgloader mysql://root:password@localhost/kre8iv_clients \
          postgresql://postgres:password@db.xxx.supabase.co:5432/postgres

# 3. Apply schema refinements
psql -h db.xxx.supabase.co -U postgres -d postgres \
  -f migration/scripts/convert-schema.sql

# 4. Verify schema
psql -h db.xxx.supabase.co -U postgres -d postgres
\dt  # List tables
\d users  # Describe users table
```

#### Day 3-4: RLS Policies

```bash
# Apply RLS policies
psql -h db.xxx.supabase.co -U postgres -d postgres \
  -f migration/scripts/rls-policies.sql

# Test RLS
psql -h db.xxx.supabase.co -U postgres -d postgres

# Try as authenticated user
SET LOCAL role authenticated;
SET LOCAL request.jwt.claim.sub = 'user-uuid-here';
SELECT * FROM requests;  # Should only see user's client requests
```

#### Day 5: Verification

```bash
# Check row counts
psql -h db.xxx.supabase.co -U postgres -d postgres -c "
SELECT
  schemaname,
  tablename,
  n_live_tup as row_count
FROM pg_stat_user_tables
WHERE schemaname = 'public'
ORDER BY tablename;
"

# Verify indexes created
psql -h db.xxx.supabase.co -U postgres -d postgres -c "
SELECT
  schemaname,
  tablename,
  indexname
FROM pg_indexes
WHERE schemaname = 'public'
ORDER BY tablename, indexname;
"
```

### Step 4: Run Week 2 - Data Migration (5 days)

#### Day 1-2: User Migration

```bash
cd migration/scripts

# Install dependencies
pnpm install

# Set environment variables
export MYSQL_HOST=localhost
export MYSQL_USER=root
export MYSQL_PASSWORD=your_password
export MYSQL_DATABASE=kre8iv_clients
export SUPABASE_URL=https://xxx.supabase.co
export SUPABASE_SERVICE_KEY=eyJhbGc...

# Run user migration
npx tsx migrate-users.ts

# Check output
# ✅ Successfully migrated: 150 users
# ❌ Failed: 0 users
# ⏱️  Duration: 45.2s

# Verify in Supabase Dashboard
# Go to Authentication → Users
```

#### Day 3-4: Full Data Migration

```bash
# Run data migration (all 130+ tables)
npx tsx migrate-data.ts

# This will take 30-60 minutes depending on data size
# Monitor progress in terminal

# Verify completion
ls -lh id-mappings.json  # ID mapping file created
```

#### Day 5: Verification

Run through `/migration/VERIFICATION_CHECKLIST.md`:

- [ ] Row counts match
- [ ] Foreign keys preserved
- [ ] Polymorphic relationships working
- [ ] RLS policies enforced
- [ ] User login works
- [ ] Sample data spot checks

### Step 5: Set Up Next.js Development (Week 3)

```bash
cd migration/nextjs-app

# Install dependencies
pnpm install

# Copy environment variables
cp .env.example .env.local

# Update .env.local with your actual values
nano .env.local

# Run development server
pnpm dev

# Open http://localhost:3000
```

**Test the application**:

1. Try to access `/dashboard` - should redirect to `/login`
2. Create a test user in Supabase Auth
3. Login and access `/dashboard/requests`
4. Verify RLS is working (can only see own client's data)

### Step 6: Install shadcn/ui Components

```bash
# Initialize shadcn/ui
npx shadcn-ui@latest init

# Install core components
npx shadcn-ui@latest add button card input select table \
  dialog sheet form textarea badge avatar separator tabs \
  dropdown-menu popover label toast

# Components are copied to components/ui/
```

### Step 7: Continue with Weekly Tasks

Follow `/migration/WEEKLY_BREAKDOWN.md` for detailed day-by-day tasks.

---

## 🔧 Development Workflow

### Daily Routine

```bash
# 1. Pull latest changes
git pull origin main

# 2. Start development server
cd migration/nextjs-app
pnpm dev

# 3. Make changes

# 4. Test changes
pnpm test
pnpm lint
pnpm type-check

# 5. Commit and push
git add .
git commit -m "feat: add client management pages"
git push origin feature/client-management

# 6. Create PR for review
```

### Testing Strategy

```bash
# Unit tests (Vitest)
pnpm test

# E2E tests (Playwright)
pnpm test:e2e

# Type checking
pnpm type-check

# Linting
pnpm lint

# Build test
pnpm build
```

### Database Schema Changes

```bash
# 1. Update Drizzle schema files
# Edit lib/db/schema/*.ts

# 2. Generate migration
pnpm db:generate

# 3. Push to database
pnpm db:push

# 4. Verify in Drizzle Studio
pnpm db:studio
```

---

## 📊 Tracking Progress

### GitHub Project Board

Create columns:

- **Backlog** - All tasks from WEEKLY_BREAKDOWN.md
- **In Progress** - Current week's tasks
- **In Review** - Completed, awaiting code review
- **Done** - Merged to main

### Weekly Status Reports

Every Friday, send status email using template from WEEKLY_BREAKDOWN.md:

```markdown
Week 3: Next.js Foundation

Accomplishments:

- ✅ Initialized Next.js 15 project
- ✅ Set up Supabase client/server helpers
- ✅ Implemented client list page

In Progress:

- 🔄 Client detail page
- 🔄 Client form component

Blockers:

- None

Next Week Plan:

- [ ] Complete client management
- [ ] Start service request pages

Risks & Concerns:

- None at this time
```

---

## 🆘 Getting Help

### If You Get Stuck

1. **Check Documentation**:
   - `/migration/docs/nextjs-examples.md` - Code examples
   - `/migration/TECH_STACK.md` - Technology decisions
   - `/migration/VERIFICATION_CHECKLIST.md` - Testing guide

2. **Search Issues**:
   - Supabase Discord: https://discord.supabase.com
   - Next.js Discord: https://discord.gg/nextjs
   - Stack Overflow tags: nextjs, supabase, drizzle-orm

3. **Review Migration Plan**:
   - Reread the relevant week in WEEKLY_BREAKDOWN.md
   - Check if you skipped a prerequisite step

### Common Issues

**Issue**: `Error: Invalid Supabase URL`
**Solution**: Check `.env.local` has correct NEXT_PUBLIC_SUPABASE_URL

**Issue**: `Error: relation "requests" does not exist`
**Solution**: Run database migrations first (Week 1-2)

**Issue**: `Error: Cannot find module '@/components/ui/button'`
**Solution**: Run `npx shadcn-ui@latest add button`

**Issue**: RLS blocking all queries
**Solution**: Check user is authenticated and has correct client_id in metadata

---

## ✅ Checklist Before Week 1

- [ ] Read all migration documentation
- [ ] Get team buy-in on plan and timeline
- [ ] Create Supabase project (Pro plan)
- [ ] Create Vercel account
- [ ] Set up Stripe account
- [ ] Set up Resend account
- [ ] Set up Inngest account
- [ ] Set up Sentry account
- [ ] Backup Laravel database
- [ ] Create GitHub project board
- [ ] Schedule daily standups
- [ ] Assign team roles

---

**Ready to begin? Start with Week 1 Day 1 in WEEKLY_BREAKDOWN.md!**

**Good luck with the migration! 🚀**
