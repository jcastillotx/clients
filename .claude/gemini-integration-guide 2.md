# Google Gemini Integration Guide

## Overview

Google Gemini has been integrated into your Laravel client portal for advanced image processing, brand styling analysis, and frontend design assistance. Gemini excels at multimodal tasks combining text and vision.

## Setup

### 1. Get Gemini API Key

1. Visit [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Create a new API key
3. Copy the key (starts with `AIza...`)

### 2. Add to Environment

Edit your `.env` file:

```env
# Google Gemini Configuration
GEMINI_API_KEY=your_api_key_here
GEMINI_DEFAULT_MODEL=gemini-2.0-flash-exp
```

### 3. Update Configuration

Add Gemini to `config/ai-providers.php`:

```php
'providers' => [
    // ... existing providers ...

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'api_base' => env('GEMINI_API_BASE', 'https://generativelanguage.googleapis.com/v1'),
        'default_model' => env('GEMINI_DEFAULT_MODEL', 'gemini-2.0-flash-exp'),
        'vision_model' => env('GEMINI_VISION_MODEL', 'gemini-2.0-flash-exp'),
    ],
],
```

Add pricing info:

```php
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

### 4. Update AIProviderManager

Edit `app/Services/AI/AIProviderManager.php` and add Gemini to the `provider()` method:

```php
public function provider(string $provider): AIProviderInterface
{
    return match ($provider) {
        'openai' => app(OpenAIService::class)->configure($this->resolveProviderConfig($provider)),
        'claude' => app(ClaudeService::class)->configure($this->resolveProviderConfig($provider)),
        'openrouter' => app(OpenRouterService::class)->configure($this->resolveProviderConfig($provider)),
        'perplexity' => app(PerplexityService::class)->configure($this->resolveProviderConfig($provider)),
        'asksage' => app(AskSageService::class)->configure($this->resolveProviderConfig($provider)),
        'gemini' => app(GeminiService::class)->configure($this->resolveProviderConfig($provider)), // Add this line
        default => throw new RuntimeException("Unknown AI provider: {$provider}"),
    };
}
```

### 5. Run Migration

```bash
php artisan migrate
```

### 6. Activate in Admin Panel

1. Navigate to `/admin/settings` → Integrations tab
2. Find Gemini in AI Providers list
3. Enter your API key
4. Set status to "Active"
5. Test connection

## Available Models

### Gemini 2.0 Flash (Recommended)
- **Model**: `gemini-2.0-flash-exp`
- **Best for**: Fast multimodal tasks, image analysis, general chat
- **Context**: 1M tokens
- **Cost**: $0.075 per 1M input tokens, $0.30 per 1M output tokens

### Gemini 1.5 Pro
- **Model**: `gemini-1.5-pro`
- **Best for**: Complex reasoning, longer contexts
- **Context**: 2M tokens
- **Cost**: $1.25 per 1M input tokens, $5.00 per 1M output tokens

### Gemini 1.5 Flash
- **Model**: `gemini-1.5-flash`
- **Best for**: Fast, cost-effective tasks
- **Context**: 1M tokens
- **Cost**: $0.075 per 1M input tokens, $0.30 per 1M output tokens

### Gemini 1.5 Flash-8B
- **Model**: `gemini-1.5-flash-8b`
- **Best for**: High-volume, simple tasks
- **Context**: 1M tokens
- **Cost**: $0.0375 per 1M input tokens, $0.15 per 1M output tokens

## Use Cases

### 1. Brand Logo Analysis

Extract colors, fonts, and style from a client's logo:

```php
use App\Services\BrandGuidelines\GeminiBrandAnalyzer;

$analyzer = app(GeminiBrandAnalyzer::class);

$result = $analyzer->analyzeLogo('path/to/logo.png');

// Returns:
// [
//     'raw_analysis' => '...',
//     'structured_data' => [
//         'color_palette' => [...],
//         'typography' => [...],
//         'design_style' => '...',
//     ],
//     'tokens_used' => [...],
//     'cost' => 0.0012,
// ]
```

### 2. Generate Complete Brand Guidelines

Create comprehensive brand guidelines from multiple assets:

```php
$result = $analyzer->generateCompleteGuidelines(
    [
        'path/to/logo.png',
        'path/to/mockup1.png',
        'path/to/color-palette.png',
    ],
    [
        'name' => 'Acme Corp',
        'industry' => 'Technology',
        'target_audience' => 'B2B SaaS companies',
        'client_id' => $client->id,
    ]
);

// Returns complete markdown guidelines document
echo $result['guidelines'];
```

### 3. Extract Color Palette

Get a structured color palette from any image:

```php
$palette = $analyzer->extractColorPalette('path/to/brand-image.png');

// Returns:
// [
//     'palette' => [
//         'primary' => [
//             ['hex' => '#1E3A8A', 'name' => 'Navy Blue'],
//         ],
//         'secondary' => [...],
//         'accents' => [...],
//         'harmony' => 'Complementary',
//         'accessibility' => [...],
//     ],
// ]
```

### 4. Generate Tailwind CSS Config

Create a Tailwind config from brand colors:

```php
$tailwindConfig = $analyzer->generateTailwindConfig($palette['palette']);

// Returns ready-to-use tailwind.config.js code
file_put_contents('tailwind.config.js', $tailwindConfig);
```

### 5. Analyze Frontend Design Mockup

Get implementation suggestions from a design mockup:

```php
use App\Services\AI\GeminiService;

$gemini = app(GeminiService::class)->configure([
    'api_key' => config('ai-providers.providers.gemini.api_key'),
]);

$result = $gemini->analyzeFrontendDesign('path/to/mockup.png', [
    'framework' => 'Tailwind CSS + Livewire',
    'output_format' => 'code', // or 'component'
]);

// Returns detailed analysis with:
// - Layout structure
// - CSS classes
// - Responsive breakpoints
// - Component breakdown
// - Sample code
```

### 6. Audit Design Compliance

Check if a design follows brand guidelines:

```php
$audit = $analyzer->auditDesignCompliance(
    'path/to/new-design.png',
    $brandGuidelines
);

// Returns:
// [
//     'compliance_score' => 85,
//     'structured_audit' => [
//         'color_usage' => 'Correct',
//         'typography' => 'Needs improvement',
//         'issues_found' => [...],
//         'recommendations' => [...],
//     ],
// ]
```

### 7. Direct Image Analysis

Analyze any image with custom prompts:

```php
$result = $gemini->analyzeImage(
    'path/to/screenshot.png',
    'Describe the user interface layout and suggest improvements for mobile responsiveness.',
    [
        'task_type' => 'ui_analysis',
        'temperature' => 0.4,
    ]
);

echo $result['text']; // AI response
```

## Livewire Component Usage

### Admin Brand Guidelines Generator

A ready-to-use Livewire component for brand guideline generation:

**Route** (add to `routes/web.php`):
```php
use App\Http\Livewire\Admin\BrandGuidelines\BrandGuidelinesGenerator;

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/brand-guidelines', BrandGuidelinesGenerator::class)
        ->name('admin.brand-guidelines');
});
```

**Navigation** (add to admin menu):
```blade
<li class="nav-item">
    <a href="{{ route('admin.brand-guidelines') }}" class="nav-link">
        <i class="nav-icon fas fa-palette"></i>
        <p>Brand Guidelines</p>
    </a>
</li>
```

**Features**:
- Upload logo and brand assets
- Generate complete guidelines with AI
- Extract color palette with visual preview
- Generate Tailwind CSS config
- Download guidelines as markdown
- Track tokens and cost

## Advanced Integration Examples

### 1. Custom Frontend Design Helper

Create a Livewire component for design assistance:

```php
<?php

namespace App\Http\Livewire\Admin;

use App\Services\AI\GeminiService;
use Livewire\Component;
use Livewire\WithFileUploads;

class FrontendDesignHelper extends Component
{
    use WithFileUploads;

    public $mockupFile;
    public $framework = 'Tailwind CSS + Livewire';
    public $analysis = null;

    public function analyzeDesign()
    {
        $this->validate([
            'mockupFile' => 'required|image|max:10240',
        ]);

        $gemini = app(GeminiService::class)->configure([
            'api_key' => config('ai-providers.providers.gemini.api_key'),
        ]);

        $tempPath = $this->mockupFile->store('temp', 'local');
        $fullPath = storage_path("app/{$tempPath}");

        $result = $gemini->analyzeFrontendDesign($fullPath, [
            'framework' => $this->framework,
            'output_format' => 'code',
        ]);

        $this->analysis = $result['text'];

        Storage::disk('local')->delete($tempPath);
    }

    public function render()
    {
        return view('livewire.admin.frontend-design-helper');
    }
}
```

### 2. Client Brand Asset Analyzer

Allow clients to upload their logo for instant brand analysis:

```php
<?php

namespace App\Http\Livewire\Client;

use App\Services\BrandGuidelines\GeminiBrandAnalyzer;
use Livewire\Component;
use Livewire\WithFileUploads;

class BrandAssetAnalyzer extends Component
{
    use WithFileUploads;

    public $logo;
    public $colors = null;

    public function analyze()
    {
        $this->validate(['logo' => 'required|image|max:5120']);

        $analyzer = app(GeminiBrandAnalyzer::class);

        $tempPath = $this->logo->store('temp', 'local');
        $fullPath = storage_path("app/{$tempPath}");

        $result = $analyzer->extractColorPalette($fullPath);
        $this->colors = $result['palette'];

        Storage::disk('local')->delete($tempPath);
    }

    public function render()
    {
        return view('livewire.client.brand-asset-analyzer');
    }
}
```

### 3. Document Design Review Service

Review document layouts and suggest improvements:

```php
<?php

namespace App\Services;

use App\Services\AI\GeminiService;

class DocumentDesignReviewer
{
    public function __construct(
        protected GeminiService $gemini
    ) {}

    public function reviewDocument(string $documentPath): array
    {
        $prompt = "Review this document's design and provide feedback on:\n\n"
            ."1. Layout and spacing\n"
            ."2. Typography hierarchy\n"
            ."3. Color usage and contrast\n"
            ."4. Visual balance\n"
            ."5. Readability\n"
            ."6. Professional appearance\n"
            ."7. Specific improvement suggestions\n\n"
            ."Rate each aspect from 1-10 and provide actionable recommendations.";

        return $this->gemini->analyzeImage($documentPath, $prompt, [
            'task_type' => 'document_design_review',
            'temperature' => 0.5,
        ]);
    }
}
```

## Best Practices

### 1. Image Quality
- Use high-resolution images for better analysis (minimum 1024px width)
- PNG format preferred for logos and graphics
- JPG acceptable for photographs and mockups

### 2. Prompt Engineering
- Be specific about what you want analyzed
- Request structured output (JSON, markdown, etc.)
- Include examples of desired output format
- Specify technical requirements (framework, constraints)

### 3. Cost Management
- Use `gemini-1.5-flash-8b` for simple, high-volume tasks
- Use `gemini-2.0-flash-exp` for most multimodal work
- Reserve `gemini-1.5-pro` for complex reasoning
- Monitor usage in `/admin/ai/usage`

### 4. Error Handling
```php
try {
    $result = $analyzer->analyzeLogo($logoPath);
} catch (\RuntimeException $e) {
    Log::error('Gemini analysis failed', [
        'error' => $e->getMessage(),
        'logo_path' => $logoPath,
    ]);

    // Fallback to basic analysis or notify user
    session()->flash('error', 'Unable to analyze logo. Please try again.');
}
```

### 5. Caching Results
```php
use Illuminate\Support\Facades\Cache;

$cacheKey = "brand_analysis_{$client->id}_{$logoHash}";

$analysis = Cache::remember($cacheKey, now()->addDays(7), function () use ($analyzer, $logoPath) {
    return $analyzer->analyzeLogo($logoPath);
});
```

## Rate Limits

Gemini API has the following limits:

- **Free tier**: 15 requests per minute
- **Pay-as-you-go**: 1000 requests per minute
- **Quota**: 50K requests per day

Monitor usage and implement rate limiting in your application.

## Testing

Test Gemini integration:

```bash
# Test API key
php artisan tinker

>>> $gemini = app(App\Services\AI\GeminiService::class)->configure([
...   'api_key' => env('GEMINI_API_KEY'),
... ]);
>>> $gemini->validateApiKey();
// Should return true

# Test text generation
>>> $result = $gemini->generateText('Explain Tailwind CSS in one sentence.');
>>> echo $result;

# Test image analysis (provide a real image path)
>>> $result = $gemini->analyzeImage('/path/to/test-image.png', 'Describe this image.');
>>> echo $result['text'];
```

## Troubleshooting

### API Key Issues
- Verify key is active in Google AI Studio
- Check `.env` file has correct key
- Ensure no whitespace around key

### Image Upload Failures
- Check file size limits (max 10MB by default)
- Verify image format is supported (PNG, JPG, GIF, WebP)
- Ensure proper file permissions

### Timeout Errors
- Increase timeout in `GeminiService.php`:
  ```php
  ->timeout(120) // 2 minutes
  ```
- For large images, resize before upload
- Use async jobs for batch processing

### JSON Parsing Issues
If AI doesn't return valid JSON:
- Request explicit JSON format in prompt
- Use fallback parsing (extract from code blocks)
- Adjust temperature (lower = more structured)

## Integration Checklist

- [ ] Get Gemini API key
- [ ] Add to `.env` file
- [ ] Update `config/ai-providers.php`
- [ ] Update `AIProviderManager.php`
- [ ] Run migration
- [ ] Test API connection
- [ ] Add route for brand guidelines generator
- [ ] Update admin navigation
- [ ] Test logo analysis
- [ ] Test brand guidelines generation
- [ ] Test color palette extraction
- [ ] Configure rate limiting
- [ ] Set up cost monitoring
- [ ] Create documentation for team

## Next Steps

1. **Customize prompts** in `GeminiBrandAnalyzer` for your specific needs
2. **Add more use cases** like social media content review, email design
3. **Build workflows** combining Gemini vision with other AI providers
4. **Create templates** for common brand analysis scenarios
5. **Integrate with existing features** like document library, marketing toolkit

## Support

For issues specific to Gemini integration:
- Check Gemini API docs: https://ai.google.dev/docs
- Review error logs: `storage/logs/laravel.log`
- Monitor AI usage: `/admin/ai/usage`
- Test in AI Admin panel: `/admin/ai/providers`
