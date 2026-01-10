# Claude Code Instructions for Kre8iv Designs Client Portal

## About This Project

This is a **Laravel 11** client management portal for Kre8iv Designs, featuring comprehensive client services management, AI-powered workflows, social media management, brand monitoring, and marketing tools.

**Project Type**: Production Laravel application with Livewire 3 frontend
**Primary Language**: PHP 8.2+ (Laravel 11)
**Database**: MySQL 8.0+
**Frontend**: Livewire 3 + Tailwind CSS + AdminLTE 3

## Quick Reference

### Key Directories
- `app/Http/Livewire/` - Livewire components (most UI features)
- `app/Models/` - Eloquent models
- `app/Services/` - Business logic and integrations
- `app/Jobs/` - Async/queued jobs
- `resources/views/livewire/` - Livewire component views
- `config/` - Configuration files
- `database/migrations/` - Database schema
- `routes/web.php` - Web routes

### Important Files
- `.env` - Environment configuration
- `config/client-portal.php` - Portal-specific settings
- `config/ai-providers.php` - AI provider configuration
- `config/features.php` - Feature gating system
- `README.md` - Full project documentation
- `docs/` - Extended documentation

## Working with This Codebase

### Before Making Changes

1. **Read the relevant documentation**:
   - Check `README.md` for general setup
   - Review `/docs/developer/tech-schematic.md` for architecture
   - Check `/docs/feature-gating.md` for feature flags
   - Review existing code patterns in similar features

2. **Understand the tech stack**:
   - Laravel 11 MVC + Livewire 3 for interactive UI
   - Spatie Laravel Permission for roles/permissions
   - Laravel Breeze for authentication
   - Tailwind CSS + AdminLTE for styling

3. **Check for related features**:
   - Use `grep` or file search to find similar implementations
   - Look for existing services before creating new ones
   - Check if a Livewire component already exists for similar functionality

### Code Patterns to Follow

#### 1. Livewire Components
- Place in `app/Http/Livewire/` with proper namespace
- Views in `resources/views/livewire/`
- Use proper authorization in mount/actions
- Follow existing naming conventions (e.g., `RequestIndex`, `AdminRequestManagement`)

#### 2. Models
- Use proper relationships (hasMany, belongsTo, etc.)
- Add fillable/guarded properties
- Include relevant scopes for common queries
- Add casts for dates/JSON fields

#### 3. Services
- Place domain logic in `app/Services/`
- Keep controllers/Livewire thin
- Use dependency injection
- Return structured data (arrays, DTOs, models)

#### 4. Jobs
- Place in `app/Jobs/`
- Implement queue interface for async processing
- Add proper error handling
- Use job batching for bulk operations

#### 5. Authorization
- Use policies for model authorization
- Check permissions in Livewire components
- Available roles: admin, staff, client
- Use `@can` directives in views

### Database Changes

1. **Always create migrations**:
   ```bash
   php artisan make:migration create_table_name
   ```

2. **Follow naming conventions**:
   - Table names: plural, snake_case
   - Foreign keys: `{table}_id`
   - Pivot tables: alphabetical order (e.g., `client_user`)

3. **Include rollback logic**:
   - Always define `down()` method
   - Test rollback before committing

### Testing

1. **Run tests before committing**:
   ```bash
   php artisan test
   ```

2. **Tests use SQLite in-memory** by default
3. **Some integration tests may warn** if external APIs aren't configured (acceptable)

### Code Style

1. **Use Laravel Pint**:
   ```bash
   ./vendor/bin/pint
   ```

2. **Check formatting**:
   ```bash
   ./vendor/bin/pint --test
   ```

3. **Follow PSR-12** coding standards

### Common Tasks

#### Adding a New Feature
1. Check if feature gating is needed (`config/features.php`)
2. Create migration if database changes needed
3. Create/update models with relationships
4. Create service class for business logic
5. Create Livewire component for UI
6. Add routes in `routes/web.php`
7. Update permissions if needed
8. Add tests
9. Update documentation

#### Modifying Existing Features
1. Find the Livewire component in `app/Http/Livewire/`
2. Check the corresponding view in `resources/views/livewire/`
3. Review related services in `app/Services/`
4. Update tests
5. Run `./vendor/bin/pint` for code style

#### Working with AI Features
- AI providers configured in `config/ai-providers.php`
- Default provider: set via `AI_DEFAULT_PROVIDER` env var
- Supported: OpenAI, Anthropic, OpenRouter, Perplexity
- Usage tracking: `ai_usage_logs` table
- Safety/compliance: `ai_safety_logs` table

#### Working with Integrations
- Stripe: Payment processing, webhooks
- AWS S3: Cloud storage
- Dropbox: OAuth + file sync
- Google Drive: OAuth + file sync
- Social platforms: Facebook, LinkedIn, Twitter, Bluesky, Pinterest, TikTok
- Brand monitoring: NewsAPI, Yelp, Google Places, Reddit, YouTube

## Environment Variables

### Required for Basic Operation
- `DB_*` - Database credentials
- `APP_KEY` - Application key (generate with `php artisan key:generate`)
- `APP_URL` - Application URL

### Optional but Recommended
- `MAIL_*` - Email configuration
- `STRIPE_*` - Payment processing
- `AI_*` - AI provider keys
- Social OAuth credentials for social media features
- Cloud storage credentials for S3/Dropbox/Drive

See `.env.example` for complete list.

## Development Workflow

### Starting Development Server
```bash
php artisan serve
npm run dev  # For asset watching
```

### Database Setup
```bash
php artisan migrate
php artisan db:seed  # Only in local/dev
```

### Building Assets
```bash
npm run build  # Production
npm run dev    # Development with watching
```

### Queue Worker (for async jobs)
```bash
php artisan queue:work
```

### Scheduler (for cron jobs)
Add to crontab:
```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Important Notes

### Security
- All client data is scoped to their account (check `client_id` columns)
- Use policies for authorization checks
- CSRF protection on all forms (automatic with Livewire)
- Validate all inputs
- Sanitize user content in views (Blade auto-escapes)

### Performance
- Use eager loading for relationships (`with()`)
- Cache frequently accessed config
- Queue long-running jobs
- Use database transactions for multi-step operations

### Livewire Best Practices
- Keep components focused (single responsibility)
- Use `wire:loading` for better UX
- Emit events for component communication
- Use query string for shareable state
- Optimize for wire:poll usage

### Don't
- Don't commit `.env` file
- Don't use seeded credentials in production
- Don't skip migrations
- Don't hardcode configuration values
- Don't bypass authorization checks
- Don't create new files without reading existing patterns first

## Getting Help

1. Check `README.md` for general setup and features
2. Review `docs/` folder for specific topics:
   - `docs/developer/` - Developer guides
   - `docs/deployment/` - Deployment guides
   - `docs/manuals/` - User manuals
3. Look at existing similar code for patterns
4. Check Laravel 11 documentation: https://laravel.com/docs/11.x
5. Check Livewire 3 documentation: https://livewire.laravel.com/docs

## Common Gotchas

1. **Bootstrap cache directory**: Must be writable
   ```bash
   mkdir -p bootstrap/cache && chmod -R 775 bootstrap/cache
   ```

2. **Storage link**: Required for public file access
   ```bash
   php artisan storage:link
   ```

3. **Config cache**: Clear after .env changes
   ```bash
   php artisan config:clear
   ```

4. **Livewire components**: Must extend `Livewire\Component`

5. **Permissions**: Seed roles/permissions before testing
   ```bash
   php artisan db:seed --class=RoleAndPermissionSeeder
   ```
