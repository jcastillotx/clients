# Gemini Integration - Quick Setup

This guide will get Gemini integrated into your project in 5 minutes.

## Step 1: Get API Key (2 minutes)

1. Go to [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Click "Create API Key"
3. Copy the key (starts with `AIza...`)

## Step 2: Environment Setup (1 minute)

Add to `.env`:

```bash
GEMINI_API_KEY=your_api_key_here
GEMINI_DEFAULT_MODEL=gemini-2.0-flash-exp
```

## Step 3: Configuration (1 minute)

Edit `config/ai-providers.php` and add Gemini to the providers array:

```php
'providers' => [
    // ... existing providers (openai, claude, etc.) ...

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'api_base' => env('GEMINI_API_BASE', 'https://generativelanguage.googleapis.com/v1'),
        'default_model' => env('GEMINI_DEFAULT_MODEL', 'gemini-2.0-flash-exp'),
        'vision_model' => env('GEMINI_VISION_MODEL', 'gemini-2.0-flash-exp'),
    ],
],

'pricing' => [
    // ... existing pricing ...

    'gemini' => [
        'gemini-2.0-flash-exp' => ['input' => 0.000075, 'output' => 0.0003],
        'gemini-1.5-pro' => ['input' => 0.00125, 'output' => 0.005],
        'gemini-1.5-flash' => ['input' => 0.000075, 'output' => 0.0003],
        'gemini-1.5-flash-8b' => ['input' => 0.0000375, 'output' => 0.00015],
    ],
],
```

## Step 4: Update Provider Manager (1 minute)

Edit `app/Services/AI/AIProviderManager.php`, find the `provider()` method and add:

```php
public function provider(string $provider): AIProviderInterface
{
    return match ($provider) {
        'openai' => app(OpenAIService::class)->configure($this->resolveProviderConfig($provider)),
        'claude' => app(ClaudeService::class)->configure($this->resolveProviderConfig($provider)),
        'openrouter' => app(OpenRouterService::class)->configure($this->resolveProviderConfig($provider)),
        'perplexity' => app(PerplexityService::class)->configure($this->resolveProviderConfig($provider)),
        'asksage' => app(AskSageService::class)->configure($this->resolveProviderConfig($provider)),
        'gemini' => app(GeminiService::class)->configure($this->resolveProviderConfig($provider)), // Add this
        default => throw new RuntimeException("Unknown AI provider: {$provider}"),
    };
}
```

## Step 5: Run Migration

```bash
php artisan migrate
```

## Step 6: Add Routes

Add to `routes/web.php`:

```php
use App\Http\Livewire\Admin\BrandGuidelines\BrandGuidelinesGenerator;

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/brand-guidelines', BrandGuidelinesGenerator::class)
        ->name('admin.brand-guidelines');
});
```

## Step 7: Test It!

```bash
php artisan tinker
```

```php
$gemini = app(App\Services\AI\GeminiService::class)->configure([
    'api_key' => env('GEMINI_API_KEY'),
]);

// Test basic text
echo $gemini->generateText('Explain Tailwind CSS in one sentence.');

// Verify it works
$gemini->validateApiKey(); // Should return true
```

## Done! 🎉

You can now:

1. **Visit `/admin/brand-guidelines`** to use the UI
2. **Use Gemini in code** for image analysis, brand guidelines, etc.

## Quick Examples

### Analyze a logo:

```php
use App\Services\BrandGuidelines\GeminiBrandAnalyzer;

$analyzer = app(GeminiBrandAnalyzer::class);
$result = $analyzer->analyzeLogo('storage/app/logo.png');

print_r($result['structured_data']);
```

### Extract colors from an image:

```php
$palette = $analyzer->extractColorPalette('storage/app/brand-image.png');

foreach ($palette['palette']['primary'] as $color) {
    echo "{$color['name']}: {$color['hex']}\n";
}
```

### Analyze a design mockup:

```php
use App\Services\AI\GeminiService;

$gemini = app(GeminiService::class)->configure([
    'api_key' => config('ai-providers.providers.gemini.api_key'),
]);

$result = $gemini->analyzeFrontendDesign('storage/app/mockup.png', [
    'framework' => 'Tailwind CSS + Livewire',
]);

echo $result['text'];
```

## Next Steps

- Read the full guide: `.claude/gemini-integration-guide.md`
- Customize prompts in `app/Services/BrandGuidelines/GeminiBrandAnalyzer.php`
- Add navigation link to admin menu for brand guidelines
- Explore more use cases in the guide

## Troubleshooting

**API Key not working?**
- Check it's correctly set in `.env`
- Verify no extra spaces
- Ensure it's enabled in Google AI Studio

**Image analysis failing?**
- Check file exists and is readable
- Ensure image is under 10MB
- Try with a simpler PNG/JPG first

**Need help?**
- Check logs: `storage/logs/laravel.log`
- See full guide: `.claude/gemini-integration-guide.md`
