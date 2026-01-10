# Quick Reference Guide

## Essential Paths

### Application Structure
```
app/Http/Livewire/          # UI Components (most features here)
app/Models/                 # Database models
app/Services/               # Business logic
app/Jobs/                   # Background jobs
resources/views/livewire/   # Component views
config/client-portal.php    # Portal configuration
routes/web.php              # Routes
```

### Key Models
- `User` - System users (staff/clients)
- `Client` - Client organizations
- `Request` - Service requests
- `Invoice` - Billing
- `Contract` - Agreements
- `Document` - Files
- `Payment` - Transactions

## Common Patterns

### Creating a Livewire Component

```bash
# Create component
php artisan make:livewire Admin/FeatureName

# Results in:
# app/Http/Livewire/Admin/FeatureName.php
# resources/views/livewire/admin/feature-name.blade.php
```

### Component Template

```php
<?php

namespace App\Http\Livewire\Admin;

use Livewire\Component;
use App\Models\ModelName;

class FeatureName extends Component
{
    public $property;

    protected $rules = [
        'property' => 'required|min:3',
    ];

    public function mount()
    {
        // Initialize
        $this->authorize('access admin panel');
    }

    public function save()
    {
        $this->validate();

        ModelName::create([
            'property' => $this->property,
            'client_id' => auth()->user()->client_id,
        ]);

        session()->flash('message', 'Success!');
    }

    public function render()
    {
        return view('livewire.admin.feature-name');
    }
}
```

### Model Template

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ModelName extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'client_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
```

### Service Template

```php
<?php

namespace App\Services\FeatureName;

use App\Models\ModelName;

class FeatureService
{
    public function processData(array $data): ModelName
    {
        // Business logic here
        return ModelName::create($data);
    }

    public function calculateMetrics(ModelName $model): array
    {
        return [
            'metric1' => 100,
            'metric2' => 200,
        ];
    }
}
```

## Database Operations

### Migration Commands
```bash
php artisan make:migration create_table_name          # Create new table
php artisan make:migration add_column_to_table        # Add columns
php artisan migrate                                   # Run migrations
php artisan migrate:rollback                          # Undo last migration
php artisan migrate:fresh --seed                      # Refresh all (DEV ONLY)
```

### Migration Template

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_name', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index('status');
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
};
```

### Querying Data

```php
// Basic query
$items = ModelName::where('status', 'active')->get();

// With relationships (eager loading)
$items = ModelName::with('client', 'user')->get();

// Scoped by client
$items = ModelName::where('client_id', auth()->user()->client_id)->get();

// Pagination
$items = ModelName::latest()->paginate(10);

// Create
$item = ModelName::create([
    'name' => 'Test',
    'client_id' => auth()->user()->client_id,
]);

// Update
$item->update(['status' => 'completed']);

// Delete
$item->delete();
```

## Authorization

### Checking Permissions

```php
// In controller/Livewire
$this->authorize('access admin panel');

// Check permission
if (auth()->user()->can('edit requests')) {
    // ...
}

// In Blade
@can('access admin panel')
    <div>Admin content</div>
@endcan

// Check role
if (auth()->user()->hasRole('admin')) {
    // ...
}
```

### Available Roles
- `super-admin` - Full access
- `admin` - Admin panel access
- `staff` - Limited admin access
- `client` - Client portal access

### Common Permissions
- `access admin panel`
- `manage clients`
- `manage users`
- `manage requests`
- `manage invoices`
- `manage contracts`
- `view reports`

## Livewire Patterns

### Properties & Binding

```php
class Component extends Component
{
    public $name;           // Two-way binding
    public $email;

    protected $rules = [
        'name' => 'required',
        'email' => 'required|email',
    ];
}
```

```blade
<input type="text" wire:model="name">
<input type="email" wire:model.blur="email">  {{-- Update on blur --}}
<input type="text" wire:model.live="search">  {{-- Real-time --}}
```

### Actions

```php
public function save()
{
    $this->validate();
    // Save logic
}

public function delete($id)
{
    ModelName::findOrFail($id)->delete();
    session()->flash('message', 'Deleted!');
}
```

```blade
<button wire:click="save">Save</button>
<button wire:click="delete({{ $item->id }})">Delete</button>
```

### Loading States

```blade
<button wire:click="save" wire:loading.attr="disabled">
    <span wire:loading.remove>Save</span>
    <span wire:loading>Saving...</span>
</button>

<div wire:loading wire:target="save">
    Processing...
</div>
```

### Events

```php
// Emit event
$this->dispatch('itemCreated', id: $item->id);

// Listen to event
protected $listeners = ['itemCreated' => 'handleItemCreated'];

public function handleItemCreated($id)
{
    // Handle event
}
```

## Testing

### Feature Test Template

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_feature()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/feature-route');

        $response->assertStatus(200);
    }

    public function test_feature_requires_authentication()
    {
        $response = $this->get('/feature-route');

        $response->assertRedirect('/login');
    }
}
```

### Livewire Test Template

```php
use Livewire\Livewire;
use App\Http\Livewire\FeatureName;

public function test_component_can_save_data()
{
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(FeatureName::class)
        ->set('name', 'Test Name')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('itemCreated');

    $this->assertDatabaseHas('table_name', [
        'name' => 'Test Name',
    ]);
}
```

## Configuration

### Request Types
Edit `config/client-portal.php`:
```php
'request_types' => [
    'web_development' => 'Web Development',
    'graphic_design' => 'Graphic Design',
    // Add more...
],
```

### Feature Gating
Edit `config/features.php`:
```php
'ai_assistants' => [
    'basic' => false,
    'pro' => true,
    'enterprise' => true,
],
```

Check in code:
```php
if (auth()->user()->client->hasFeature('ai_assistants')) {
    // Show AI features
}
```

## CLI Commands

### Development
```bash
php artisan serve              # Start dev server (localhost:8000)
npm run dev                    # Watch assets
php artisan queue:work         # Process jobs
php artisan schedule:work      # Run scheduler (dev)
php artisan tinker            # REPL console
```

### Database
```bash
php artisan migrate            # Run migrations
php artisan db:seed           # Run seeders
php artisan migrate:fresh     # Drop all & re-migrate
php artisan migrate:rollback  # Undo last migration
```

### Cache
```bash
php artisan optimize:clear    # Clear all caches
php artisan config:cache      # Cache config
php artisan route:cache       # Cache routes
php artisan view:cache        # Cache views
```

### Code Quality
```bash
./vendor/bin/pint             # Fix code style
./vendor/bin/pint --test      # Check style
php artisan test              # Run tests
php artisan test --filter=FeatureTest  # Run specific test
```

### Make Commands
```bash
php artisan make:livewire Name            # Livewire component
php artisan make:model Name -m            # Model + migration
php artisan make:migration name           # Migration
php artisan make:seeder NameSeeder        # Seeder
php artisan make:factory NameFactory      # Factory
php artisan make:test NameTest            # Test
php artisan make:controller NameController # Controller
```

## Environment Variables

### Required
```env
APP_KEY=                 # Generate with: php artisan key:generate
APP_URL=http://localhost:8000
DB_DATABASE=database_name
DB_USERNAME=root
DB_PASSWORD=
```

### Optional but Common
```env
MAIL_MAILER=log                        # Use 'log' for local dev
STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
AI_DEFAULT_PROVIDER=openai
OPENAI_API_KEY=sk-xxx
```

## Debugging

### Log Messages
```php
\Log::info('Debug message', ['data' => $variable]);
\Log::error('Error occurred', ['exception' => $e]);
```

### Dump & Die
```php
dd($variable);              // Dump and die
dump($variable);            // Dump and continue

// In Blade
@dd($variable)
@dump($variable)
```

### Query Logging
```php
\DB::enableQueryLog();
// ... your queries
dd(\DB::getQueryLog());
```

### Livewire Debugging
```php
// In component
public function mount()
{
    dd($this->property);
}
```

### Log File
```bash
tail -f storage/logs/laravel.log
```

## Common Gotchas

1. **Clear cache after .env changes**:
   ```bash
   php artisan config:clear
   ```

2. **Storage permissions**:
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

3. **Composer autoload after new classes**:
   ```bash
   composer dump-autoload
   ```

4. **Livewire not updating**:
   ```bash
   php artisan livewire:discover
   php artisan view:clear
   ```

5. **Client data scoping** - Always filter by client_id:
   ```php
   // Good
   ModelName::where('client_id', auth()->user()->client_id)->get();

   // Bad (shows all clients' data)
   ModelName::all();
   ```

## Helpful Queries

### Find where route is defined:
```bash
grep -r "route('route.name'" routes/
```

### Find Livewire component:
```bash
find app/Http/Livewire -name "*ComponentName*"
```

### Find where a class is used:
```bash
grep -r "ClassName" app/
```

### Find database table usage:
```bash
grep -r "table_name" app/
```

## Production Checklist

Before deploying:
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Run `npm run build`
- [ ] Run tests: `php artisan test`
- [ ] Check `.env` has all required variables
- [ ] Setup cron job for scheduler
- [ ] Setup queue worker daemon
- [ ] Verify storage permissions
