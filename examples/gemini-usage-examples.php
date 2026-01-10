<?php

/**
 * Gemini Integration Usage Examples
 *
 * This file demonstrates various ways to use Gemini in your Laravel application.
 * These are working examples you can adapt to your needs.
 */

use App\Services\AI\GeminiService;
use App\Services\BrandGuidelines\GeminiBrandAnalyzer;
use Illuminate\Support\Facades\Storage;

// ============================================================================
// EXAMPLE 1: Basic Text Generation
// ============================================================================

function example_basic_text_generation()
{
    $gemini = app(GeminiService::class)->configure([
        'api_key' => config('ai-providers.providers.gemini.api_key'),
    ]);

    $result = $gemini->generateText('Write a professional email subject line for a website redesign proposal.');

    echo "Generated text: {$result}\n";
}

// ============================================================================
// EXAMPLE 2: Analyze Logo for Brand Colors
// ============================================================================

function example_analyze_logo()
{
    $analyzer = app(GeminiBrandAnalyzer::class);

    // Path can be: storage path, local file, or URL
    $logoPath = storage_path('app/public/clients/acme/logo.png');

    $result = $analyzer->analyzeLogo($logoPath, [
        'client_id' => 1,
        'task_type' => 'brand_analysis',
    ]);

    echo "Logo Analysis:\n";
    print_r($result['structured_data']);

    echo "\nTokens used: {$result['tokens_used']['total']}\n";
    echo "Cost: \${$result['cost']}\n";

    return $result;
}

// ============================================================================
// EXAMPLE 3: Generate Complete Brand Guidelines
// ============================================================================

function example_generate_brand_guidelines()
{
    $analyzer = app(GeminiBrandAnalyzer::class);

    $assets = [
        storage_path('app/public/clients/acme/logo.png'),
        storage_path('app/public/clients/acme/website-mockup.png'),
        storage_path('app/public/clients/acme/color-palette.png'),
    ];

    $result = $analyzer->generateCompleteGuidelines($assets, [
        'name' => 'Acme Corporation',
        'industry' => 'Technology',
        'target_audience' => 'B2B SaaS companies and enterprise clients',
        'client_id' => 1,
    ]);

    // Save guidelines to file
    $filename = 'acme_brand_guidelines.md';
    Storage::put("brand-guidelines/{$filename}", $result['guidelines']);

    echo "Brand guidelines generated and saved to: storage/app/brand-guidelines/{$filename}\n";
    echo "Tokens: {$result['tokens_used']['total']}\n";
    echo "Cost: \${$result['cost']}\n";

    return $result;
}

// ============================================================================
// EXAMPLE 4: Extract Color Palette
// ============================================================================

function example_extract_colors()
{
    $analyzer = app(GeminiBrandAnalyzer::class);

    $imagePath = storage_path('app/public/inspiration/design-sample.png');

    $result = $analyzer->extractColorPalette($imagePath);

    $palette = $result['palette'];

    echo "Primary Colors:\n";
    foreach ($palette['primary'] ?? [] as $color) {
        echo "  {$color['name']}: {$color['hex']}\n";
    }

    echo "\nSecondary Colors:\n";
    foreach ($palette['secondary'] ?? [] as $color) {
        echo "  {$color['name']}: {$color['hex']}\n";
    }

    echo "\nColor Harmony: {$palette['harmony']}\n";

    return $palette;
}

// ============================================================================
// EXAMPLE 5: Generate Tailwind CSS Config from Colors
// ============================================================================

function example_generate_tailwind_config()
{
    $analyzer = app(GeminiBrandAnalyzer::class);

    // First extract colors
    $paletteResult = $analyzer->extractColorPalette(
        storage_path('app/public/clients/acme/logo.png')
    );

    // Then generate Tailwind config
    $tailwindConfig = $analyzer->generateTailwindConfig($paletteResult['palette']);

    // Save to file
    Storage::put('tailwind-configs/acme-tailwind.config.js', $tailwindConfig);

    echo "Tailwind config generated!\n";
    echo $tailwindConfig;

    return $tailwindConfig;
}

// ============================================================================
// EXAMPLE 6: Analyze Frontend Design Mockup
// ============================================================================

function example_analyze_frontend_design()
{
    $gemini = app(GeminiService::class)->configure([
        'api_key' => config('ai-providers.providers.gemini.api_key'),
    ]);

    $mockupPath = storage_path('app/public/mockups/dashboard-design.png');

    $result = $gemini->analyzeFrontendDesign($mockupPath, [
        'framework' => 'Tailwind CSS + Livewire',
        'output_format' => 'code',
        'client_id' => 1,
    ]);

    echo "Design Analysis:\n";
    echo $result['text'];
    echo "\n\nTokens: {$result['tokens']['total']}\n";

    return $result;
}

// ============================================================================
// EXAMPLE 7: Audit Design for Brand Compliance
// ============================================================================

function example_audit_design_compliance()
{
    $analyzer = app(GeminiBrandAnalyzer::class);

    // Load existing brand guidelines
    $guidelines = [
        'colors' => [
            'primary' => '#1E3A8A',
            'secondary' => '#10B981',
        ],
        'fonts' => [
            'heading' => 'Inter',
            'body' => 'Open Sans',
        ],
    ];

    $newDesignPath = storage_path('app/public/designs/new-landing-page.png');

    $audit = $analyzer->auditDesignCompliance($newDesignPath, $guidelines);

    echo "Compliance Score: {$audit['compliance_score']}/100\n\n";
    echo "Audit Results:\n";
    print_r($audit['structured_audit']);

    return $audit;
}

// ============================================================================
// EXAMPLE 8: Batch Process Multiple Logos
// ============================================================================

function example_batch_process_logos()
{
    $analyzer = app(GeminiBrandAnalyzer::class);

    $logos = [
        'client1' => storage_path('app/public/clients/client1/logo.png'),
        'client2' => storage_path('app/public/clients/client2/logo.png'),
        'client3' => storage_path('app/public/clients/client3/logo.png'),
    ];

    $results = [];

    foreach ($logos as $clientName => $logoPath) {
        echo "Analyzing {$clientName}...\n";

        try {
            $result = $analyzer->extractColorPalette($logoPath);
            $results[$clientName] = $result['palette'];

            echo "  ✓ Colors extracted\n";
        } catch (\Exception $e) {
            echo "  ✗ Failed: {$e->getMessage()}\n";
        }

        // Rate limiting: wait 1 second between requests
        sleep(1);
    }

    return $results;
}

// ============================================================================
// EXAMPLE 9: Image Analysis with Custom Prompt
// ============================================================================

function example_custom_image_analysis()
{
    $gemini = app(GeminiService::class)->configure([
        'api_key' => config('ai-providers.providers.gemini.api_key'),
    ]);

    $screenshotPath = storage_path('app/public/screenshots/competitor-website.png');

    $customPrompt = "Analyze this competitor's website screenshot and identify:\n"
        ."1. Main color scheme\n"
        ."2. Layout patterns used\n"
        ."3. Call-to-action buttons and their placement\n"
        ."4. Typography choices\n"
        ."5. What makes it effective or ineffective\n"
        ."6. 3 specific improvements we could apply to our design\n\n"
        ."Format as a structured report.";

    $result = $gemini->analyzeImage($screenshotPath, $customPrompt, [
        'task_type' => 'competitor_analysis',
        'temperature' => 0.4,
    ]);

    echo $result['text'];

    return $result;
}

// ============================================================================
// EXAMPLE 10: Multi-Image Brand Guidelines (Controller Example)
// ============================================================================

namespace App\Http\Controllers;

use App\Services\BrandGuidelines\GeminiBrandAnalyzer;
use Illuminate\Http\Request;

class BrandGuidelinesController extends Controller
{
    public function generate(Request $request, GeminiBrandAnalyzer $analyzer)
    {
        $request->validate([
            'brand_name' => 'required|string',
            'industry' => 'required|string',
            'logo' => 'required|image|max:10240',
            'assets.*' => 'nullable|image|max:10240',
        ]);

        // Store uploaded files
        $logoPath = $request->file('logo')->store('brand-analysis', 'local');
        $assetPaths = [$logoPath];

        if ($request->hasFile('assets')) {
            foreach ($request->file('assets') as $asset) {
                $assetPaths[] = $asset->store('brand-analysis', 'local');
            }
        }

        // Convert to full paths
        $fullPaths = array_map(
            fn ($path) => storage_path("app/{$path}"),
            $assetPaths
        );

        // Generate guidelines
        $result = $analyzer->generateCompleteGuidelines($fullPaths, [
            'name' => $request->input('brand_name'),
            'industry' => $request->input('industry'),
            'target_audience' => $request->input('target_audience'),
            'client_id' => auth()->user()->client_id,
        ]);

        // Clean up temp files
        foreach ($assetPaths as $path) {
            Storage::disk('local')->delete($path);
        }

        return response()->json([
            'success' => true,
            'guidelines' => $result['guidelines'],
            'tokens_used' => $result['tokens_used'],
            'cost' => $result['cost'],
        ]);
    }
}

// ============================================================================
// EXAMPLE 11: Job Queue for Large Batch Processing
// ============================================================================

namespace App\Jobs;

use App\Models\Client;
use App\Services\BrandGuidelines\GeminiBrandAnalyzer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateBrandGuidelinesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Client $client,
        public array $assetPaths
    ) {}

    public function handle(GeminiBrandAnalyzer $analyzer)
    {
        $result = $analyzer->generateCompleteGuidelines($this->assetPaths, [
            'name' => $this->client->company_name,
            'industry' => $this->client->industry,
            'target_audience' => $this->client->target_audience,
            'client_id' => $this->client->id,
        ]);

        // Save to client record
        $this->client->update([
            'brand_guidelines' => $result['guidelines'],
            'brand_colors' => $result['color_palette'] ?? null,
        ]);

        // Notify client
        $this->client->notify(new BrandGuidelinesReadyNotification($result));
    }
}

// Dispatch the job:
// GenerateBrandGuidelinesJob::dispatch($client, $assetPaths);

// ============================================================================
// EXAMPLE 12: Cached Analysis to Save Costs
// ============================================================================

use Illuminate\Support\Facades\Cache;

function example_cached_logo_analysis($logoPath)
{
    $analyzer = app(GeminiBrandAnalyzer::class);

    // Create cache key from file hash
    $fileHash = md5_file($logoPath);
    $cacheKey = "logo_analysis_{$fileHash}";

    // Cache for 30 days
    return Cache::remember($cacheKey, now()->addDays(30), function () use ($analyzer, $logoPath) {
        return $analyzer->analyzeLogo($logoPath);
    });
}

// ============================================================================
// EXAMPLE 13: Real-time Design Feedback API
// ============================================================================

namespace App\Http\Controllers\Api;

use App\Services\AI\GeminiService;
use Illuminate\Http\Request;

class DesignFeedbackController extends Controller
{
    public function analyze(Request $request, GeminiService $gemini)
    {
        $request->validate([
            'image' => 'required|string', // Base64 encoded image
            'feedback_type' => 'required|in:layout,colors,typography,accessibility',
        ]);

        $prompts = [
            'layout' => 'Analyze the layout structure and spacing. Suggest improvements.',
            'colors' => 'Analyze the color scheme. Check contrast ratios and accessibility.',
            'typography' => 'Review typography choices. Suggest font pairings and hierarchy.',
            'accessibility' => 'Audit for WCAG 2.1 AA compliance. List issues and fixes.',
        ];

        $result = $gemini->analyzeImage(
            $request->input('image'), // Data URI
            $prompts[$request->input('feedback_type')],
            ['task_type' => 'design_feedback']
        );

        return response()->json([
            'feedback' => $result['text'],
            'tokens_used' => $result['tokens'],
            'cost' => $result['estimated_cost'],
        ]);
    }
}

// ============================================================================
// Run Examples
// ============================================================================

// Uncomment to test:
// example_basic_text_generation();
// example_analyze_logo();
// example_generate_brand_guidelines();
// example_extract_colors();
// example_generate_tailwind_config();
// example_analyze_frontend_design();
