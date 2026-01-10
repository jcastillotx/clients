# Claude Code Documentation

This directory contains comprehensive documentation for working with the Kre8iv Designs Client Portal using Claude Code.

## Documentation Files

### 📋 [instructions.md](instructions.md)
**Start here!** Main working instructions for Claude Code when modifying this codebase.

**Contains**:
- Quick reference for key directories and files
- Code patterns to follow
- Common development tasks
- Important notes and gotchas
- Security best practices

**Use this when**: You need to understand how to work with this codebase.

---

### 🎯 [project-context.md](project-context.md)
Business context and purpose of this application.

**Contains**:
- Business overview
- What the application does
- Core business flows
- User roles and permissions
- Business rules
- Revenue model
- Integration strategy
- Deployment context

**Use this when**: You need to understand the "why" behind features and business logic.

---

### 🛠️ [tech-stack.md](tech-stack.md)
Technical architecture and stack details.

**Contains**:
- Technology stack (Laravel, Livewire, etc.)
- Application architecture
- Database architecture
- Key dependencies
- Runtime components (scheduler, queues, webhooks)
- Configuration system
- Performance optimizations
- Security architecture

**Use this when**: You need to understand the technical implementation details.

---

### ✨ [features-overview.md](features-overview.md)
Comprehensive breakdown of all features.

**Contains**:
- Core portal features (requests, contracts, invoices, documents)
- AI-powered features (assistants, estimation, analysis)
- Social media management
- Brand monitoring
- Marketing toolkit
- Cloud storage integrations
- Account management
- Communication features
- Admin tools
- Feature gating system

**Use this when**: You need to understand what features exist and how they work.

---

### 👨‍💻 [development-guide.md](development-guide.md)
Development workflows, testing, and deployment.

**Contains**:
- Initial setup instructions
- Development workflow
- Common development tasks
- Testing guide
- Code quality tools
- Debugging techniques
- Database management
- Asset management
- Git workflow
- Deployment checklist
- Troubleshooting

**Use this when**: You need to set up the project, develop features, or deploy.

---

## Quick Navigation

### I want to...

**Understand this project** → Start with [project-context.md](project-context.md)

**Work on code** → Read [instructions.md](instructions.md)

**Add a new feature** → Follow [development-guide.md](development-guide.md#creating-a-new-feature)

**Understand the tech stack** → Review [tech-stack.md](tech-stack.md)

**Find existing features** → Search [features-overview.md](features-overview.md)

**Debug an issue** → Check [development-guide.md](development-guide.md#debugging)

**Deploy changes** → See [development-guide.md](development-guide.md#deployment)

---

## Additional Resources

### In the Parent Directory

- **[../README.md](../README.md)** - Main project README with setup and features
- **[../docs/](../docs/)** - Extended documentation
  - `docs/developer/` - Developer guides
  - `docs/deployment/` - Deployment guides
  - `docs/manuals/` - User manuals
  - `docs/troubleshooting/` - Troubleshooting guides
- **[../DEPLOYMENT_PHASE1.md](../DEPLOYMENT_PHASE1.md)** - Phase 1 deployment summary

### Configuration Files

- **`../config/client-portal.php`** - Portal settings (request types, statuses, etc.)
- **`../config/features.php`** - Feature gating configuration
- **`../config/ai-providers.php`** - AI provider settings
- **`../.env.example`** - Environment variable reference

---

## File Organization

```
.claude/
├── README.md                 # This file - documentation index
├── instructions.md           # Main working instructions
├── project-context.md        # Business context
├── tech-stack.md            # Technical architecture
├── features-overview.md     # Feature breakdown
└── development-guide.md     # Development workflows
```

---

## Key Concepts

### This is a Laravel 11 + Livewire 3 Application

- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: Livewire 3 (interactive components)
- **Styling**: Tailwind CSS + AdminLTE
- **Database**: MySQL 8.0+
- **Authentication**: Laravel Breeze
- **Payments**: Stripe
- **AI**: Multi-provider (OpenAI, Anthropic, etc.)

### Multi-Tenant Architecture

- Data scoped by `client_id`
- Role-based access control (admin/staff/client)
- Feature gating per client tier
- Row-level security via Eloquent scopes

### Key Directories

```
app/
├── Http/Livewire/     # Livewire components (most UI)
├── Models/            # Eloquent models
├── Services/          # Business logic & integrations
└── Jobs/              # Async/queued jobs

resources/
├── views/livewire/    # Livewire views
└── views/layouts/     # Layout templates

config/
├── client-portal.php  # Portal config
├── features.php       # Feature gating
└── ai-providers.php   # AI config

database/
├── migrations/        # Database schema
└── seeders/          # Seed data
```

---

## Environment Setup Quick Reference

```bash
# Clone and install
composer install && npm install

# Configure
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed

# Build assets
npm run build

# Start development
php artisan serve  # Terminal 1
npm run dev        # Terminal 2
```

---

## Common Commands

```bash
# Development
php artisan serve                    # Start dev server
npm run dev                          # Watch assets
php artisan queue:work              # Process jobs

# Database
php artisan migrate                  # Run migrations
php artisan db:seed                 # Seed database
php artisan migrate:fresh --seed    # Refresh DB

# Code Quality
./vendor/bin/pint                   # Fix code style
php artisan test                    # Run tests

# Cache
php artisan optimize:clear          # Clear all caches
php artisan config:cache            # Cache config
```

---

## Getting Help

1. **Check this documentation first** - Start with instructions.md
2. **Review existing code** - Look for similar features
3. **Check Laravel docs** - https://laravel.com/docs/11.x
4. **Check Livewire docs** - https://livewire.laravel.com/docs
5. **Check project README** - ../README.md
6. **Review extended docs** - ../docs/

---

## Important Notes

### Security
- All client data must be scoped by `client_id`
- Use policies for authorization
- Validate all inputs
- Never commit `.env` file

### Code Style
- Run `./vendor/bin/pint` before committing
- Follow PSR-12 standards
- Keep Livewire components focused
- Put business logic in Services

### Testing
- Write tests for new features
- Run `php artisan test` before committing
- Tests use SQLite in-memory by default

### Deployment
- Never use seeded credentials in production
- Always run migrations with `--force` flag
- Cache config/routes/views in production
- Set APP_ENV=production and APP_DEBUG=false

---

Last Updated: January 2026
