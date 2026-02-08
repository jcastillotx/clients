# Kre8iv Clients Platform - Next.js

Modern client management platform built with Next.js 15, Supabase, and TypeScript.

## Current Version

- **Application**: `v1.1.0`
- **Package**: `kre8iv-clients-nextjs@1.0.0` (from `package.json`)
- **Last Updated**: 2026-02-08
- **Recent Platform Updates**:
  - Supabase Auth callback/confirm flow hardened for password recovery and magic links
  - Admin visibility improvements across dashboard and client listings
  - Expanded dashboard route coverage for marketing/admin capabilities
  - Storage integrations updated to include AWS S3 and GCS setup guidance

## Tech Stack

- **Framework**: Next.js 15 (App Router)
- **Language**: TypeScript 5.3+
- **Database**: PostgreSQL (Supabase)
- **Authentication**: Supabase Auth
- **Storage**: Supabase Storage
- **Styling**: Tailwind CSS 3.4
- **UI Components**: shadcn/ui (Radix UI)
- **Forms**: React Hook Form + Zod
- **State Management**: Server Components + TanStack Query
- **Background Jobs**: Inngest
- **Email**: Resend
- **Payments**: Stripe
- **Analytics**: Vercel Analytics + Sentry

### Core Architecture Notes

- **Auth**: Supabase Auth (email/password + magic link + password recovery), SSR session refresh in middleware
- **Authorization**: RBAC via `roles`, `user_roles`, and permission checks; admin/super_admin support
- **Data Layer**: Supabase Postgres + Drizzle schema/migrations
- **Deployment**: Vercel (Next.js build/runtime)
- **Integrations**: Stripe, Resend, Inngest, AWS S3, Google Cloud Storage

## Features

### Client Management
- Multi-tenant SaaS architecture
- Client onboarding and profiles
- Staff assignment management
- Activity tracking and audit logs

### Request Workflow
- Service request management
- Kanban board view
- SLA monitoring and alerts
- Real-time status updates
- Comment threads
- File attachments

### Invoicing & Payments
- Invoice generation and management
- Stripe payment integration
- Recurring invoice automation
- Payment reminders
- PDF invoice generation
- Multi-currency support

### Document Library
- Secure file storage
- Document versioning
- Access control and sharing
- Full-text search
- Tag-based organization

### Contract Management
- Contract lifecycle management
- E-signature workflows
- Expiration tracking
- Auto-renewal options
- Contract templates

### Admin Panel
- System dashboard with metrics
- User management with RBAC
- Email template editor
- Invoice template editor
- System health monitoring

### Background Jobs
- Invoice reminders (daily)
- Recurring invoice generation (daily)
- SLA compliance checks (every 5 minutes)
- Contract expiration checks (daily)
- Email automation

## Getting Started

### Prerequisites

- Node.js 20+
- pnpm 9+
- Supabase account
- Vercel account (for deployment)

### Installation

```bash
# Clone repository
git clone <repository-url>
cd nextjs-app

# Install dependencies
pnpm install

# Copy environment variables
cp .env.example .env.local

# Update .env.local with your credentials
```

### Database Setup

```bash
# Install Supabase CLI
npm install -g supabase

# Link to your Supabase project
supabase link --project-ref YOUR_PROJECT_REF

# Run migrations
cd lib/db/migrations
supabase db push
```

### Development

```bash
# Start development server
pnpm dev

# Open http://localhost:3000
```

### Environment Variables

See `.env.example` for all required environment variables:

- **Supabase**: Database and authentication
- **Inngest**: Background job execution
- **Resend**: Email sending
- **Stripe**: Payment processing
- **Sentry**: Error tracking (optional)
- **Session/Domain (recommended for multi-subdomain auth)**:
  - `NEXT_PUBLIC_AUTH_COOKIE_DOMAIN=.kre8ivdesigns.com`
- **Client filtering**:
  - `PARENT_CLIENT_IDS` (comma-separated client UUIDs to exclude from client listing)
  - `PARENT_COMPANY_NAMES` (comma-separated names to exclude; default includes `Kre8ivTech,Kre8iv Designs`)

## Test Accounts (Seeded)

Use the SQL seed script (provided in setup notes/chat) to create these users.

- **Default password for seeded users**: `TempPass123!`
- **Important**: rotate these passwords immediately outside development.

### Platform Accounts

- `superadmin+test@example.com` (`super_admin`)
- `admin+test@example.com` (`admin`)
- `staff+test@example.com` (`staff`)

### Client Accounts

- `admin.acme@example.com` / `staff.acme@example.com`
- `admin.blue@example.com` / `staff.blue@example.com`
- `admin.north@example.com` / `staff.north@example.com`
- `admin.sunset@example.com` / `staff.sunset@example.com`
- `admin.vertex@example.com` / `staff.vertex@example.com`

### Seeded Test Clients

- Acme Dental Group
- Blue River Law
- North Peak Fitness
- Sunset Realty Partners
- Vertex Logistics

## Project Structure

```
app/
├── (auth)/              # Authentication pages
├── (dashboard)/         # Main application
│   ├── dashboard/       # Dashboard
│   ├── requests/        # Request management
│   ├── invoices/        # Invoice management
│   ├── documents/       # Document library
│   ├── contracts/       # Contract management
│   └── admin/           # Admin panel
└── api/                 # API routes
    ├── requests/
    ├── invoices/
    ├── documents/
    ├── contracts/
    ├── admin/
    ├── webhooks/
    └── inngest/

components/
├── ui/                  # shadcn/ui components
├── admin/               # Admin-specific components
├── contracts/           # Contract components
├── documents/           # Document components
├── invoices/            # Invoice components
└── requests/            # Request components

lib/
├── db/                  # Database schemas and migrations
│   ├── schema/          # Drizzle ORM schemas
│   └── migrations/      # SQL migrations
├── email/               # Email templates and sending
├── inngest/             # Background job functions
├── rbac/                # Role-based access control
├── storage/             # File storage utilities
├── supabase/            # Supabase client
└── templates/           # Template rendering engine

docs/
├── DEPLOYMENT.md        # Production deployment guide
├── BACKGROUND_JOBS.md   # Background jobs documentation
├── TEMPLATE_SETUP.md    # Email/invoice templates
└── TESTING_CHECKLIST.md # Complete testing checklist
```

## Documentation

- [Deployment Guide](docs/DEPLOYMENT.md) - Complete production deployment
- [Background Jobs](docs/BACKGROUND_JOBS.md) - Inngest functions and webhooks
- [Template Setup](docs/TEMPLATE_SETUP.md) - Email and invoice templates
- [Testing Checklist](docs/TESTING_CHECKLIST.md) - Comprehensive test cases

## Development Workflow

### Creating New Features

1. Create database migration (if needed)
2. Update Drizzle schema
3. Create API route
4. Build UI components
5. Add validation with Zod
6. Update permissions (if needed)
7. Write tests
8. Update documentation

### Testing

```bash
# Unit tests
pnpm test

# E2E tests
pnpm test:e2e

# Type checking
pnpm type-check

# Linting
pnpm lint
```

### Deployment

```bash
# Deploy to production
vercel --prod

# Or push to main branch for automatic deployment
```

## Key Design Decisions

### Server Components First
- Use Server Components by default for better performance
- Client Components only where interactivity needed
- Reduces JavaScript bundle size significantly

### Database-First Permissions
- Row-Level Security (RLS) enforces access control at database level
- Permission checks in API routes as additional layer
- Prevents data leaks even if application code has bugs

### Type Safety Throughout
- TypeScript for all code
- Zod for runtime validation
- Drizzle ORM for type-safe database queries
- No `any` types in production code

### Progressive Enhancement
- Works without JavaScript (forms, navigation)
- Enhanced with JavaScript (real-time updates, optimistic UI)
- Accessible by default (semantic HTML, ARIA labels)

## Performance Targets

- **Page Load**: < 2 seconds
- **Database Queries**: < 100ms
- **API Response**: < 500ms
- **Background Jobs**: 99% success rate
- **Uptime**: > 99.9%

## Security

- HTTPS enforced
- Row-Level Security (RLS) on all tables
- RBAC with fine-grained permissions
- Webhook signature verification
- Input validation on all forms
- XSS protection (React auto-escaping)
- SQL injection prevention (Supabase client)
- Secure headers configured

## Support

- Issues: [GitHub Issues](https://github.com/yourusername/kre8iv-clients/issues)
- Email: support@yourdomain.com

## License

Proprietary - All rights reserved

## Acknowledgments

Built with modern web technologies:
- [Next.js](https://nextjs.org)
- [Supabase](https://supabase.com)
- [shadcn/ui](https://ui.shadcn.com)
- [Inngest](https://inngest.com)
- [Vercel](https://vercel.com)
