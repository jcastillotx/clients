# Development Guide

## Initial Setup

### 1. Prerequisites
Ensure you have installed:
- PHP 8.2 or higher
- Composer 2.x
- Node.js 18+ and NPM
- MySQL 8.0+
- Git

Required PHP extensions:
```bash
php -m | grep -E 'mbstring|xml|curl|zip|gd|sqlite3'
```

### 2. Clone and Install

```bash
# Clone repository
git clone <repository-url>
cd client-portal

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create database and configure .env
# Then run migrations
php artisan migrate

# Seed database (dev/local only)
php artisan db:seed

# Create storage link
php artisan storage:link

# Build assets
npm run build
```

### 3. Environment Configuration

Edit `.env` and configure:

**Database**:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

**Application**:
```env
APP_NAME="Kre8iv Designs Client Portal"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

**Mail** (for local testing, use Mailtrap or log driver):
```env
MAIL_MAILER=log
# Or configure SMTP
```

**Stripe** (optional for local dev):
```env
STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
```

## Development Workflow

### Starting Development Environment

```bash
# Terminal 1: Laravel development server
php artisan serve

# Terminal 2: Asset watching (Vite)
npm run dev

# Terminal 3: Queue worker (if testing queued jobs)
php artisan queue:work

# Terminal 4: Scheduler (if testing scheduled tasks)
# Run every minute or use this helper:
php artisan schedule:work
```

Access the application at: `http://localhost:8000`

### Default Credentials (after seeding)

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@kre8ivdesigns.com | password |
| Staff | staff@kre8ivdesigns.com | password |
| Client | client@demo.com | password |

## Common Development Tasks

### Creating a New Feature

#### 1. Database Changes

```bash
# Create migration
php artisan make:migration create_feature_table

# Or for adding columns
php artisan make:migration add_columns_to_table_name
```

Edit migration file in `database/migrations/`, then:

```bash
# Run migration
php artisan migrate

# Rollback last migration if needed
php artisan migrate:rollback

# Refresh all migrations (WARNING: drops all tables)
php artisan migrate:fresh --seed
```

#### 2. Create Model

```bash
php artisan make:model Feature

# With migration, factory, and seeder
php artisan make:model Feature -mfs
```

Edit model in `app/Models/Feature.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $fillable = ['name', 'description', 'client_id'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
```

#### 3. Create Livewire Component

```bash
# Create component with view
php artisan make:livewire Client/FeatureManager

# Or for nested paths
php artisan make:livewire Admin/Features/FeatureIndex
```

This creates:
- Component class: `app/Http/Livewire/Client/FeatureManager.php`
- View file: `resources/views/livewire/client/feature-manager.blade.php`

Example component:

```php
<?php

namespace App\Http\Livewire\Client;

use Livewire\Component;
use App\Models\Feature;

class FeatureManager extends Component
{
    public $features;
    public $name;
    public $description;

    protected $rules = [
        'name' => 'required|min:3',
        'description' => 'required',
    ];

    public function mount()
    {
        $this->loadFeatures();
    }

    public function loadFeatures()
    {
        $this->features = Feature::where('client_id', auth()->user()->client_id)
            ->latest()
            ->get();
    }

    public function save()
    {
        $this->validate();

        Feature::create([
            'name' => $this->name,
            'description' => $this->description,
            'client_id' => auth()->user()->client_id,
        ]);

        $this->reset(['name', 'description']);
        $this->loadFeatures();

        session()->flash('message', 'Feature created successfully!');
    }

    public function render()
    {
        return view('livewire.client.feature-manager');
    }
}
```

#### 4. Create Service (if business logic needed)

```bash
# Create service directory and file manually
mkdir -p app/Services/Features
touch app/Services/Features/FeatureService.php
```

Example service:

```php
<?php

namespace App\Services\Features;

use App\Models\Feature;

class FeatureService
{
    public function createFeature(array $data): Feature
    {
        // Complex business logic here
        return Feature::create($data);
    }

    public function calculateMetrics(Feature $feature): array
    {
        // Complex calculations
        return [
            'usage' => 100,
            'performance' => 95,
        ];
    }
}
```

#### 5. Add Routes

Edit `routes/web.php`:

```php
use App\Http\Livewire\Client\FeatureManager;

Route::middleware(['auth'])->group(function () {
    Route::get('/features', FeatureManager::class)->name('features.index');
});
```

#### 6. Add Navigation Link

Edit appropriate layout/navigation file:
- Client menu: `resources/views/layouts/app.blade.php`
- Admin menu: `resources/views/layouts/admin.blade.php`

```blade
<li class="nav-item">
    <a href="{{ route('features.index') }}" class="nav-link">
        <i class="nav-icon fas fa-star"></i>
        <p>Features</p>
    </a>
</li>
```

### Working with Existing Features

#### Finding Related Code

1. **Find Livewire component**:
   ```bash
   # By feature name
   find app/Http/Livewire -name "*Feature*"

   # By route
   grep -r "route('features" routes/
   ```

2. **Find service**:
   ```bash
   find app/Services -name "*Feature*"
   ```

3. **Find model**:
   ```bash
   find app/Models -name "Feature.php"
   ```

4. **Find view**:
   ```bash
   find resources/views -name "*feature*"
   ```

#### Modifying Livewire Components

1. Edit component class in `app/Http/Livewire/`
2. Edit view in `resources/views/livewire/`
3. Test changes in browser (Livewire auto-reloads)
4. Check browser console for JavaScript errors
5. Check Laravel logs: `storage/logs/laravel.log`

#### Adding to Existing Tables

```bash
# Create migration
php artisan make:migration add_status_to_features_table

# Edit migration
Schema::table('features', function (Blueprint $table) {
    $table->string('status')->default('active');
    $table->index('status');
});

# Run migration
php artisan migrate
```

Don't forget to:
1. Add to model's `$fillable` array
2. Add to `$casts` if special type
3. Update validation rules
4. Update views/forms

## Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/FeatureTest.php

# Run with coverage
php artisan test --coverage

# Run specific test method
php artisan test --filter test_user_can_create_feature
```

### Writing Tests

```bash
# Create test
php artisan make:test FeatureTest

# Create unit test
php artisan make:test FeatureServiceTest --unit
```

Example feature test:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_features()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/features');

        $response->assertStatus(200);
    }

    public function test_user_can_create_feature()
    {
        $user = User::factory()->create();

        $data = [
            'name' => 'Test Feature',
            'description' => 'Test Description',
        ];

        Livewire::actingAs($user)
            ->test(FeatureManager::class)
            ->set('name', $data['name'])
            ->set('description', $data['description'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('features', $data);
    }
}
```

### Browser Testing (Dusk)

```bash
# Install Chrome driver
php artisan dusk:chrome-driver

# Run Dusk tests
php artisan dusk

# Run specific test
php artisan dusk tests/Browser/FeatureTest.php
```

## Code Quality

### Laravel Pint (Code Style)

```bash
# Fix code style issues
./vendor/bin/pint

# Check without modifying
./vendor/bin/pint --test

# Fix specific directory
./vendor/bin/pint app/Services
```

### Static Analysis (optional)

```bash
# Install PHPStan
composer require --dev phpstan/phpstan

# Run analysis
./vendor/bin/phpstan analyse app
```

## Debugging

### Laravel Log

View logs in real-time:
```bash
tail -f storage/logs/laravel.log
```

Add debug logs:
```php
\Log::info('Debug message', ['data' => $variable]);
\Log::error('Error occurred', ['exception' => $e->getMessage()]);
```

### Livewire Debugging

In component:
```php
public function mount()
{
    dd($this->property); // Dump and die
    dump($this->property); // Dump and continue
}
```

In view:
```blade
@dump($variable)
@dd($variable)
```

### Query Debugging

```php
// Enable query log
\DB::enableQueryLog();

// Your queries here
User::where('active', true)->get();

// Dump queries
dd(\DB::getQueryLog());
```

### Tinker (REPL)

```bash
php artisan tinker

# Then in tinker:
>>> $user = User::first()
>>> $user->requests()->count()
>>> Feature::factory()->create()
```

## Database Management

### Seeders

```bash
# Create seeder
php artisan make:seeder FeatureSeeder

# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=FeatureSeeder
```

### Factories

```bash
# Create factory
php artisan make:factory FeatureFactory --model=Feature
```

Example factory:

```php
public function definition(): array
{
    return [
        'name' => fake()->sentence(3),
        'description' => fake()->paragraph(),
        'client_id' => Client::factory(),
    ];
}
```

Usage:
```php
Feature::factory()->count(10)->create();
Feature::factory()->create(['name' => 'Specific Name']);
```

### Database Console

```bash
# MySQL console
php artisan db

# Or directly
mysql -u username -p database_name
```

## Asset Management

### Compiling Assets

```bash
# Development (watch for changes)
npm run dev

# Production build
npm run build

# Check for errors
npm run build -- --debug
```

### Adding CSS/JS

1. Edit files in `resources/css/` or `resources/js/`
2. Import in `resources/js/app.js` or `resources/css/app.css`
3. Rebuild: `npm run dev`

### Tailwind Configuration

Edit `tailwind.config.js`:

```javascript
module.exports = {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
    ],
    theme: {
        extend: {
            colors: {
                'brand': '#your-color',
            },
        },
    },
}
```

## Performance Optimization

### Caching

```bash
# Cache all configs
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Clear all caches
php artisan optimize:clear
```

### Query Optimization

```php
// Bad: N+1 query
$requests = Request::all();
foreach ($requests as $request) {
    echo $request->client->name; // Extra query per request
}

// Good: Eager loading
$requests = Request::with('client')->get();
foreach ($requests as $request) {
    echo $request->client->name; // No extra queries
}
```

### Livewire Optimization

```php
// Lazy load heavy data
public function loadData()
{
    $this->data = HeavyService::getData();
}

// In view:
<div wire:init="loadData">
    @if($data)
        // Display data
    @else
        Loading...
    @endif
</div>
```

## Git Workflow

### Branch Strategy

```bash
# Create feature branch
git checkout -b feature/feature-name

# Make changes and commit
git add .
git commit -m "Add feature description"

# Push to remote
git push origin feature/feature-name

# Create pull request on GitHub
```

### Commit Messages

Follow conventional commits:
```
feat: Add new feature
fix: Fix bug in feature
refactor: Refactor code
docs: Update documentation
test: Add tests
chore: Update dependencies
```

### Before Committing

```bash
# Run code style
./vendor/bin/pint

# Run tests
php artisan test

# Check for issues
git status
git diff
```

## Deployment

See `docs/deployment/production.md` and `DEPLOYMENT_PHASE1.md` for detailed deployment instructions.

### Quick Production Checklist

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Run migrations: `php artisan migrate --force`
3. Cache everything: `php artisan config:cache && php artisan route:cache && php artisan view:cache`
4. Set proper permissions: `chmod -R 755 storage bootstrap/cache`
5. Setup cron job for scheduler
6. Setup queue worker as daemon
7. Configure proper .env values (database, mail, Stripe, etc.)

## Troubleshooting

### Common Issues

**"Class not found" error**:
```bash
composer dump-autoload
```

**"Permission denied" on storage**:
```bash
chmod -R 775 storage bootstrap/cache
```

**Livewire component not found**:
```bash
php artisan livewire:discover
php artisan view:clear
```

**Changes not reflecting**:
```bash
php artisan optimize:clear
npm run build
```

**Queue jobs not processing**:
```bash
# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Restart queue worker
php artisan queue:restart
```
