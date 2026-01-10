# Project Context: Kre8iv Designs Client Portal

## Business Overview

**Company**: Kre8iv Designs LLC
**Product**: Client Management Portal
**Purpose**: Self-service client portal for managing service requests, contracts, invoices, and digital marketing services

## What This Application Does

This is a comprehensive client management and service delivery platform that enables:

### For Clients
- Submit and track service requests (web development, design, marketing, etc.)
- View and digitally sign contracts
- View and pay invoices via Stripe
- Access and manage documents
- Connect cloud storage (S3, Dropbox, Google Drive)
- Manage social media accounts and approve posts
- Request project estimates with AI assistance
- Track brand mentions and reputation
- Access marketing tools and website audits

### For Staff/Admin
- Manage client accounts and service requests
- Generate contracts and invoices
- Track payments and subscriptions
- Monitor team workload and capacity
- Review and approve client work
- Access AI assistants for various tasks
- Monitor brand mentions across the web
- Generate and schedule reports
- Manage integrations and system settings

## Core Business Flows

### 1. Service Request Lifecycle
1. Client submits request with details and attachments
2. Request enters pending status
3. Admin reviews and assigns to staff
4. Staff works on request, updates status
5. Client and staff can comment/collaborate
6. Request marked as completed
7. Activity logged throughout

### 2. Invoice & Payment Flow
1. Admin creates invoice for client
2. Invoice sent to client (email notification)
3. Client views invoice in portal
4. Client pays via Stripe integration
5. Payment webhook updates invoice status
6. Receipt emailed to client
7. Overdue invoices tracked automatically

### 3. Contract Signing Flow
1. Admin generates contract (with AI assistance option)
2. Contract sent to client
3. Client reviews contract in portal
4. Client signs digitally
5. Signed contract stored and available for download
6. Contract expiration monitoring

### 4. Social Media Management Flow
1. Client connects social accounts (OAuth)
2. AI assists with post creation
3. Posts added to content calendar
4. Client reviews and approves posts
5. Scheduled publishing to platforms
6. Performance tracking and notifications

### 5. Brand Monitoring Flow
1. System monitors multiple sources (news, social, reviews)
2. Mentions collected and sentiment analyzed
3. Negative mentions flagged for attention
4. Staff/client notified of important mentions
5. Response tracking for reputation management

## User Roles & Permissions

### Client
- Access own data only (scoped by client_id)
- Create/view/edit own requests
- View own invoices and contracts
- Upload documents
- Connect storage and social accounts
- Request estimates
- View own brand mentions

### Staff
- View assigned requests
- Update request status
- Comment on requests
- Limited admin functions based on permissions

### Admin
- Full access to all features
- Manage clients and users
- Create/edit invoices and contracts
- Access all reports and analytics
- Configure system settings
- Manage integrations
- View all brand mentions

## Key Business Rules

### Service Requests
- Request types defined in `config/client-portal.php`
- Status workflow: pending → in_review → approved → in_progress → completed
- Priority levels: low, medium, high, urgent
- All changes logged in activity log
- Clients can only see/edit their own requests

### Invoices
- Sequential numbering with prefix (INV-XXXX)
- Support for line items, discounts, taxes
- Payment integration with Stripe
- Auto-mark overdue after due date (via scheduler)
- PDF generation for download

### Contracts
- Support for multiple contract types
- Digital signature capture
- Contract terms acceptance tracking
- Expiration date monitoring
- AI-assisted contract generation

### Documents
- Per-client storage quotas
- Version tracking
- AI-powered analysis and chat
- Cloud storage sync (S3, Dropbox, Drive)
- Secure access control

### AI Features
- Multiple provider support (OpenAI, Anthropic, etc.)
- Usage tracking and cost monitoring
- Safety/compliance logging
- Feature gating per client tier
- Human review queue for sensitive tasks

## Revenue Model

### Client Tiers
Feature gating system allows different capabilities per tier:
- Basic clients: Core portal features
- Pro clients: AI features, advanced integrations
- Enterprise: Full feature set + white labeling

### Payment Processing
- Stripe integration for invoice payments
- Support for one-time and recurring payments
- Webhook handling for payment events
- Refund tracking

## Integration Strategy

### Current Integrations
- **Payments**: Stripe (invoices, webhooks)
- **Cloud Storage**: AWS S3, Dropbox, Google Drive
- **Social Media**: Facebook, LinkedIn, Twitter, Bluesky, Pinterest, TikTok
- **AI Providers**: OpenAI, Anthropic, OpenRouter, Perplexity
- **Brand Monitoring**: NewsAPI, Yelp, Google Places, Reddit, YouTube, Google Search, Bing
- **Email**: Configurable SMTP

### Integration Approach
- OAuth 2.0 for social/storage connections
- Webhook receivers for async updates
- Queue jobs for external API calls
- Rate limiting and error handling
- Graceful degradation when services unavailable

## Data Privacy & Security

### Client Data Isolation
- All queries scoped by client_id
- Row-level security via Eloquent scopes
- Policy-based authorization
- Activity logging for audit trail

### Compliance Features
- GDPR privacy center
- Data export requests
- Account deletion with cascade
- Privacy policy acceptance tracking
- Session management and 2FA

### Security Measures
- Laravel authentication (Breeze)
- CSRF protection
- SQL injection prevention (Eloquent ORM)
- XSS protection (Blade templating)
- Rate limiting on auth routes
- Password hashing (bcrypt)
- File upload validation

## Deployment Context

### Environments
- **Local/Dev**: Full seeded data, all features enabled
- **Production**: cPanel hosting, controlled feature rollout

### Hosting Requirements
- PHP 8.2+ with extensions (mbstring, xml, curl, zip, gd, sqlite3)
- MySQL 8.0+ database
- Composer 2.x
- Node.js 18+ for asset compilation
- Cron access for scheduler
- Queue worker process

### Configuration Management
- Environment-driven via .env
- Feature flags in config/features.php
- White-label branding support
- Per-environment settings

## Future Roadmap Considerations

### Planned Features (from codebase scaffolding)
- SEO monitoring and reporting
- Content marketing calendar
- Campaign management
- Lead nurturing
- Review management
- Partner/referral program
- Advanced analytics and predictive insights
- QBR (Quarterly Business Review) builder

### Technical Debt
- Some features are MVP/scaffolding stage
- Integration tests have warnings for optional services
- Brand monitoring uses free/low-cost APIs (may need upgrades)
- Social media APIs have varying limitations

## Business Priorities

1. **Client Self-Service**: Reduce support burden through intuitive portal
2. **Automation**: AI-powered workflows for efficiency
3. **Revenue Growth**: Upsell tracking, renewal management
4. **Quality**: Activity logging, review queues, safety checks
5. **Scale**: Multi-tenant architecture, feature gating
6. **White-Label**: Branding system for reseller opportunities
