# Laravel to Next.js Migration

**Status**: Planning Complete, Ready for Implementation
**Timeline**: 8-10 weeks
**Team Size**: 4-5 developers (2 backend, 2-3 full-stack)

## Quick Links

- [Migration Plan](./MIGRATION_PLAN.md) - Full implementation plan
- [Week-by-Week Breakdown](./WEEKLY_BREAKDOWN.md) - Detailed weekly tasks
- [Tech Stack](./TECH_STACK.md) - Complete technology choices
- [Rollback Plan](./ROLLBACK_PLAN.md) - Emergency procedures
- [Verification Checklist](./VERIFICATION_CHECKLIST.md) - Post-migration testing

## Current State

- **Laravel 11** + Livewire 3 multi-tenant SaaS
- **165+ Eloquent models** across 130+ MySQL tables
- **Critical UX/UI issues**: Bootstrap+Tailwind conflicts, monolithic components, poor mobile responsiveness
- **Complex features**: Client management, invoicing, contracts, AI automation, webhooks

## Target State

- **Next.js 15** (App Router) + TypeScript
- **Supabase** (PostgreSQL + Auth + Storage + Realtime)
- **shadcn/ui** for consistent, accessible component library
- **Inngest** for durable background jobs
- **Vercel** for deployment and edge functions

## Migration Strategy

**Big Bang Approach** (8-10 weeks):

1. **Week 1-2**: Database migration (MySQL → PostgreSQL with RLS)
2. **Week 3**: Core features (Client management)
3. **Week 4**: Service requests
4. **Week 5**: Invoicing & payments
5. **Week 6**: Documents & contracts
6. **Week 7**: Background jobs & automation
7. **Week 8**: Admin panel & testing
8. **Week 9-10**: Deployment & launch

## Why Migrate?

### Critical UX/UI Problems

1. **Bootstrap + Tailwind Conflict** (Most Severe)
   - 112 views mixing incompatible CSS frameworks
   - Unpredictable styling, visual inconsistencies
   - **Fix**: Pure Tailwind with shadcn/ui

2. **Monolithic Components**
   - SEO Dashboard: 1,107 lines
   - Branding Settings: 958 lines
   - **Fix**: Server Components, proper composition

3. **No Lazy Loading**
   - Only 1 lazy-loaded component
   - **Fix**: Next.js dynamic imports, Suspense boundaries

4. **Performance Issues**
   - 1,795 wire directives = constant HTTP requests
   - **Fix**: Server Components + TanStack Query caching

5. **Accessibility Gaps**
   - Missing ARIA attributes, poor keyboard navigation
   - **Fix**: Radix UI primitives (shadcn/ui foundation)

6. **Poor Mobile Responsiveness**
   - Mixed grid systems, overflowing tables
   - **Fix**: Mobile-first Tailwind, responsive components

## Success Criteria

- ✅ **Zero data loss** during migration
- ✅ **100% feature parity** with Laravel version
- ✅ **Single CSS framework** (Tailwind only)
- ✅ **Accessible** (WCAG 2.1 AA compliance)
- ✅ **Mobile-first** responsive design
- ✅ **Performance** <2s page loads
- ✅ **Type-safe** end-to-end TypeScript

## Getting Started

### Prerequisites

```bash
# Required versions
node -v  # 20+
pnpm -v  # 9+
```

### Environment Setup

1. **Clone and navigate**:

   ```bash
   cd /Users/jlaptop/Apps/clients/migration
   ```

2. **Review the plan**:

   ```bash
   cat MIGRATION_PLAN.md
   ```

3. **Set up Supabase project**:
   - Go to https://supabase.com
   - Create new project
   - Save credentials to `.env.local`

4. **Set up Vercel account**:
   - Go to https://vercel.com
   - Connect GitHub repository
   - Configure environment variables

### Week 1: Database Migration

```bash
# Export MySQL schema
mysqldump -u root -p --no-data kre8iv_clients > schema.sql

# Convert to PostgreSQL (manual refinement needed)
# See /migration/scripts/convert-schema.sql

# Apply to Supabase
psql -h db.xxx.supabase.co -U postgres -d postgres -f schema-postgres.sql
```

## Directory Structure

```
migration/
├── README.md (this file)
├── MIGRATION_PLAN.md
├── WEEKLY_BREAKDOWN.md
├── TECH_STACK.md
├── ROLLBACK_PLAN.md
├── VERIFICATION_CHECKLIST.md
├── scripts/
│   ├── convert-schema.sql
│   ├── migrate-data.ts
│   ├── migrate-users.ts
│   └── verify-migration.ts
├── docs/
│   ├── database-schema.md
│   ├── rls-policies.md
│   ├── api-routes.md
│   └── component-mapping.md
└── next-app/ (created in Week 3)
    ├── app/
    ├── components/
    ├── lib/
    └── ...
```

## Team Roles

### Backend Developers (2)

- Database schema conversion
- RLS policy implementation
- Data migration scripts
- API route development
- Background job migration

### Full-Stack Developers (2-3)

- Next.js application setup
- Component development (shadcn/ui)
- Form handling (React Hook Form + Zod)
- State management (Server Components + TanStack Query)
- Integration testing

### QA Engineer (1, Week 8+)

- End-to-end testing (Playwright)
- Accessibility audit
- Mobile responsiveness testing
- Performance testing
- UAT coordination

## Risk Mitigation

### High-Risk Areas

1. **Data Migration**
   - **Risk**: Data loss, corruption
   - **Mitigation**: Staging migration first, row count validation, spot checks

2. **Polymorphic Relationships**
   - **Risk**: Complex queries fail
   - **Mitigation**: Composite indexes, test extensively

3. **Large File Uploads**
   - **Risk**: 100MB+ file failures
   - **Mitigation**: Resumable uploads with chunking

4. **Background Jobs**
   - **Risk**: Missed scheduled tasks
   - **Mitigation**: Parallel running Laravel + Inngest for 1 week

### Rollback Plan

If critical issues occur:

1. **DNS revert** to Laravel (5 minutes)
2. **Database sync** back to MySQL (30 minutes)
3. **User communication** (15 minutes)

**Rollback triggers**:

- Critical bugs affecting >50% of users
- Data corruption or loss
- Payment processing failures
- Security vulnerabilities

## Communication Plan

### Stakeholders

- **Product Owner**: Weekly progress updates
- **Development Team**: Daily standups, Slack channel
- **QA**: Bug reports via GitHub Issues
- **End Users**: Pre-launch email, in-app notifications

### Status Updates

- **Weekly**: Progress report against timeline
- **Critical Issues**: Immediate Slack notification
- **Pre-Launch**: 1 week warning to users
- **Post-Launch**: 48-hour monitoring report

## Support

- **Slack**: #laravel-to-nextjs-migration
- **GitHub**: https://github.com/yourorg/clients/issues
- **Documentation**: /migration/docs/
- **Questions**: Tag @migration-team in Slack

---

**Next Steps**:

1. Review this README and migration plan
2. Provision Supabase project
3. Set up Vercel account
4. Assemble development team
5. Begin Week 1: Database migration
