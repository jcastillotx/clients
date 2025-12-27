# Feature Gating & Client Permissions

This system allows you to control which features clients can access based on their tier, contract, or explicit feature flags.

## Table of Contents

1. [Overview](#overview)
2. [Configuration](#configuration)
3. [Usage Examples](#usage-examples)
4. [Database Setup](#database-setup)
5. [Managing Features](#managing-features)

## Overview

The feature gating system provides three levels of control:

1. **Tier-Based**: Features automatically assigned based on client tier (basic, standard, professional, premium, enterprise)
2. **Contract-Based**: Features specified in the active contract's `meta` field
3. **Explicit Override**: Features explicitly enabled/disabled in `client.enabled_features`

Priority order: Explicit Disable > Explicit Enable > Contract Features > Tier Features

## Configuration

All features are defined in `config/features.php`:

```php
'tiers' => [
    'basic' => ['dashboard', 'documents', 'invoices'],
    'premium' => ['dashboard', 'documents', 'brand_monitoring', 'ai_insights'],
    // ...
]
```

### Available Features

See `config/features.php` for the complete list. Key categories:

- **Core**: `dashboard`, `documents`, `invoices`, `service_requests`
- **Brand Monitoring**: `brand_monitoring`, `brand_monitoring_news`, `brand_monitoring_reviews`, etc.
- **AI**: `ai_assistant`, `ai_insights`, `ai_document_analysis`
- **Advanced**: `api_access`, `webhooks`, `white_label_reports`
- **Research**: `research_assistant`, `competitor_monitoring`, `industry_insights`

## Usage Examples

### 1. Route Protection

Protect routes with feature middleware:

```php
// Single feature required
Route::get('/brand-monitoring', MyMentions::class)
    ->middleware('feature:brand_monitoring');

// Multiple features (client needs ANY of them)
Route::get('/analytics', AnalyticsDashboard::class)
    ->middleware('feature.any:ai_insights,advanced_analytics');

// With redirect on failure
Route::get('/premium-feature', PremiumView::class)
    ->middleware('feature:premium_feature,dashboard');
```

### 2. Blade Directives

Conditionally show UI elements:

```blade
{{-- Check single feature --}}
@feature('brand_monitoring')
    <a href="{{ route('client.brand-monitoring.my-mentions') }}">
        Brand Monitoring
    </a>
@endfeature

{{-- Check if client has ANY of the features --}}
@anyFeature('ai_assistant', 'ai_insights')
    <div class="ai-tools">
        AI Tools Available
    </div>
@endanyFeature

{{-- Check if client has ALL features --}}
@allFeatures('brand_monitoring', 'brand_monitoring_sentiment')
    <p>Full brand monitoring with sentiment analysis enabled</p>
@endallFeatures

{{-- Check if premium tier --}}
@premiumTier
    <span class="badge badge-gold">Premium Client</span>
@endpremiumTier

{{-- With else --}}
@feature('api_access')
    <a href="/api/documentation">API Documentation</a>
@else
    <a href="/upgrade">Upgrade for API Access</a>
@endfeature
```

### 3. In Controllers/Livewire

Check features programmatically:

```php
use Illuminate\Support\Facades\Auth;

class MyController extends Controller
{
    public function index()
    {
        $client = Auth::user()->client;

        // Check single feature
        if ($client->hasFeature('brand_monitoring')) {
            // Show brand monitoring data
        }

        // Check multiple (ANY)
        if ($client->hasAnyFeature(['ai_insights', 'advanced_analytics'])) {
            // Show analytics
        }

        // Check multiple (ALL)
        if ($client->hasAllFeatures(['brand_monitoring', 'brand_monitoring_sentiment'])) {
            // Show full monitoring dashboard
        }

        // Get all available features
        $features = $client->getAvailableFeatures();

        // Check tier
        if ($client->isPremiumTier()) {
            // Premium-only logic
        }

        // Get feature limits
        $monthlyRequests = $client->getFeatureLimit('service_requests', 'monthly');
        $storageGB = $client->getFeatureLimit('documents', 'storage_gb');
    }
}
```

### 4. In API Controllers

Return proper responses for feature checks:

```php
public function brandMentions(Request $request)
{
    $client = $request->user()->client;

    if (!$client->hasFeature('brand_monitoring')) {
        return response()->json([
            'error' => 'Feature not available',
            'message' => 'Brand monitoring is not included in your plan',
            'upgrade_url' => route('pricing'),
        ], 403);
    }

    // Return brand mentions
}
```

## Database Setup

### 1. Run Migration

```bash
php artisan migrate
```

This adds:
- `clients.enabled_features` (JSON field)
- `contracts.contract_type` (string field)

### 2. Assign Client Tiers

```php
// Via Eloquent
$client->update(['tier' => 'premium']);

// Via Tinker
php artisan tinker
>>> $client = Client::find(1);
>>> $client->update(['tier' => 'premium']);
```

Available tiers: `basic`, `standard`, `professional`, `premium`, `enterprise`

### 3. Create Contracts with Features

```php
$contract = Contract::create([
    'client_id' => $client->id,
    'title' => 'SEO & Brand Monitoring Package',
    'contract_type' => 'seo_package',
    'status' => 'active',
    'start_date' => now(),
    'end_date' => now()->addYear(),
    'meta' => [
        'features' => [
            'brand_monitoring',
            'brand_monitoring_news',
            'brand_monitoring_web',
            'competitor_monitoring',
        ],
        'limits' => [
            'tracked_keywords' => 25,
        ],
    ],
]);
```

## Managing Features

### Enable Feature for Client

```php
$client = Client::find(1);
$client->enableFeature('brand_monitoring');
```

This explicitly enables the feature, overriding tier defaults.

### Disable Feature for Client

```php
$client->disableFeature('api_access');
```

This explicitly disables the feature, even if included in tier or contract.

### Bulk Enable Multiple Features

```php
$client->update([
    'enabled_features' => [
        'brand_monitoring',
        'ai_insights',
        'white_label_reports',
    ]
]);
```

### Explicitly Deny a Feature

```php
// Add "-" prefix to deny
$client->update([
    'enabled_features' => [
        'brand_monitoring',      // Allow
        '-api_access',           // Explicitly deny
    ]
]);
```

### Check What Features a Client Has

```php
$client = Client::find(1);
$features = $client->getAvailableFeatures();
// Returns Collection: ['dashboard', 'documents', 'brand_monitoring', ...]

// Check specific feature
if ($features->contains('brand_monitoring')) {
    // Client has brand monitoring
}
```

### Update Contract Features

```php
$contract = $client->contracts()->active()->first();
$contract->update([
    'meta' => array_merge($contract->meta ?? [], [
        'features' => [
            'brand_monitoring',
            'brand_monitoring_sentiment',
            'ai_insights',
        ],
    ]),
]);
```

## Feature Priority Examples

### Example 1: Tier + Explicit Enable

```php
$client->tier = 'basic'; // basic tier: only core features
$client->enabled_features = ['brand_monitoring'];

$client->hasFeature('brand_monitoring'); // true (explicit enable)
$client->hasFeature('ai_insights'); // false (not in basic tier)
```

### Example 2: Contract Override

```php
$client->tier = 'basic';
$client->contracts()->active()->first()->meta = [
    'features' => ['brand_monitoring', 'ai_insights'],
];

$client->hasFeature('brand_monitoring'); // true (from contract)
$client->hasFeature('ai_insights'); // true (from contract)
```

### Example 3: Explicit Deny Takes Precedence

```php
$client->tier = 'premium'; // premium includes api_access
$client->enabled_features = ['-api_access']; // explicit deny

$client->hasFeature('api_access'); // false (explicit deny wins)
$client->hasFeature('brand_monitoring'); // true (from tier)
```

## Common Use Cases

### Use Case 1: Trial Period

Give a basic client temporary access to premium features:

```php
$client->enableFeature('brand_monitoring');
$client->enableFeature('ai_insights');

// Schedule job to remove after 30 days
```

### Use Case 2: A La Carte Services

Client on standard tier wants to add brand monitoring only:

```php
$contract = Contract::create([
    'client_id' => $client->id,
    'title' => 'Brand Monitoring Add-On',
    'contract_type' => 'addon',
    'meta' => [
        'features' => [
            'brand_monitoring',
            'brand_monitoring_news',
            'brand_monitoring_reviews',
        ],
    ],
]);
```

### Use Case 3: Feature Usage Limits

Track usage against limits:

```php
$limit = $client->getFeatureLimit('service_requests', 'monthly');
$currentUsage = $client->requests()->whereMonth('created_at', now())->count();

if ($limit && $currentUsage >= $limit) {
    // Prevent new request creation
    return redirect()->back()->with('error', 'Monthly request limit reached');
}
```

### Use Case 4: Upgrade Flow

Show upgrade CTA when feature is not available:

```blade
@feature('brand_monitoring')
    <livewire:brand-monitoring-dashboard />
@else
    <div class="upgrade-cta">
        <h3>Unlock Brand Monitoring</h3>
        <p>Track your brand across news, reviews, and social media</p>
        <a href="{{ route('pricing') }}">Upgrade to Professional Plan</a>
    </div>
@endfeature
```

## Testing

### Test Feature Access

```php
// tests/Feature/FeatureGatingTest.php
public function test_client_can_access_feature_based_on_tier()
{
    $client = Client::factory()->create(['tier' => 'premium']);
    $user = User::factory()->create(['client_id' => $client->id]);

    $this->actingAs($user)
        ->get(route('client.brand-monitoring.my-mentions'))
        ->assertOk();
}

public function test_basic_client_cannot_access_premium_feature()
{
    $client = Client::factory()->create(['tier' => 'basic']);
    $user = User::factory()->create(['client_id' => $client->id]);

    $this->actingAs($user)
        ->get(route('client.brand-monitoring.my-mentions'))
        ->assertForbidden();
}
```

## Admin Tools

### View Client Features (Admin Panel)

```blade
{{-- resources/views/admin/clients/show.blade.php --}}
<div class="card">
    <div class="card-header">
        <h4>Enabled Features</h4>
    </div>
    <div class="card-body">
        <ul>
            @foreach($client->getAvailableFeatures() as $feature)
                <li>{{ config("features.available.{$feature}.name") }}</li>
            @endforeach
        </ul>
    </div>
</div>
```

### Enable/Disable Features (Livewire Component)

```php
// app/Http/Livewire/Admin/Clients/FeatureManager.php
class FeatureManager extends Component
{
    public Client $client;
    public array $selectedFeatures = [];

    public function mount(Client $client)
    {
        $this->client = $client;
        $this->selectedFeatures = $client->enabled_features ?? [];
    }

    public function save()
    {
        $this->client->update([
            'enabled_features' => $this->selectedFeatures,
        ]);

        session()->flash('message', 'Features updated successfully');
    }

    public function render()
    {
        return view('livewire.admin.clients.feature-manager', [
            'availableFeatures' => config('features.available'),
        ]);
    }
}
```

## Best Practices

1. **Use tier-based features for standard offerings**: Define common packages in `config/features.php`
2. **Use contracts for custom deals**: Store unique feature combinations in contract meta
3. **Use explicit flags sparingly**: Only for temporary access or one-off overrides
4. **Always check features in routes AND views**: Defense in depth
5. **Provide upgrade CTAs**: When denying access, show users how to upgrade
6. **Log feature denials**: Track which features users are trying to access

## Troubleshooting

### Client can't access feature despite correct tier

1. Check if client is active: `$client->isActive()`
2. Check for explicit denials: `$client->enabled_features` for `-feature_name`
3. Check contract status: Active contract with `end_date` in future
4. Clear cache: `php artisan config:clear`

### Feature shows in UI but route is blocked

Make sure middleware on route matches Blade directive:

```php
// Route
Route::get('/feature')->middleware('feature:brand_monitoring');

// Blade
@feature('brand_monitoring') ... @endfeature
```

Both must use the same feature key.
