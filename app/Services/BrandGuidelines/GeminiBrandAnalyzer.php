<?php

namespace App\Services\BrandGuidelines;

use App\Services\AI\AIProviderManager;
use Illuminate\Support\Facades\Storage;

class GeminiBrandAnalyzer
{
    public function __construct(
        protected AIProviderManager $aiManager
    ) {}

    /**
     * Analyze logo and extract brand colors, fonts, style.
     *
     * @param  string  $logoPath  Path to logo file
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function analyzeLogo(string $logoPath, array $options = []): array
    {
        $gemini = $this->aiManager->provider('gemini');

        $prompt = "Analyze this logo and extract:\n\n"
            ."1. **Color Palette**: List all colors with hex codes (primary, secondary, accents)\n"
            ."2. **Typography**: Identify font style (serif, sans-serif, script, etc.)\n"
            ."3. **Design Style**: Modern, classic, minimalist, bold, playful, etc.\n"
            ."4. **Shape Elements**: Geometric shapes, organic forms, icons used\n"
            ."5. **Spacing & Layout**: Logo proportions, whitespace usage\n"
            ."6. **Suggested Use Cases**: Where this logo works best (web, print, dark/light backgrounds)\n\n"
            ."Format the response as structured JSON.";

        $result = $gemini->analyzeImage($logoPath, $prompt, array_merge($options, [
            'task_type' => 'brand_logo_analysis',
        ]));

        // Try to parse JSON response
        $analysis = $this->parseJsonResponse($result['text']);

        return [
            'raw_analysis' => $result['text'],
            'structured_data' => $analysis,
            'tokens_used' => $result['tokens'],
            'cost' => $result['estimated_cost'],
        ];
    }

    /**
     * Generate complete brand guidelines from multiple assets.
     *
     * @param  array<string>  $assetPaths  Paths to brand assets (logo, mockups, etc.)
     * @param  array<string, mixed>  $brandInfo  Additional brand context
     * @return array<string, mixed>
     */
    public function generateCompleteGuidelines(array $assetPaths, array $brandInfo = []): array
    {
        $gemini = $this->aiManager->provider('gemini');

        $brandName = $brandInfo['name'] ?? 'the brand';
        $industry = $brandInfo['industry'] ?? 'general';
        $targetAudience = $brandInfo['target_audience'] ?? 'general audience';

        $prompt = "Create comprehensive brand style guidelines for {$brandName} in the {$industry} industry, targeting {$targetAudience}.\n\n"
            ."Based on these brand assets, provide:\n\n"
            ."## 1. Brand Overview\n"
            ."- Mission & Values\n"
            ."- Brand Personality\n"
            ."- Voice & Tone\n\n"
            ."## 2. Visual Identity\n"
            ."- **Color Palette**\n"
            ."  - Primary colors (with hex codes)\n"
            ."  - Secondary colors\n"
            ."  - Accent colors\n"
            ."  - Color usage rules\n\n"
            ."- **Typography**\n"
            ."  - Heading fonts\n"
            ."  - Body fonts\n"
            ."  - Font pairing rules\n"
            ."  - Size hierarchy\n\n"
            ."- **Logo Usage**\n"
            ."  - Clear space requirements\n"
            ."  - Minimum sizes\n"
            ."  - Approved backgrounds\n"
            ."  - Incorrect usage examples\n\n"
            ."## 3. Design System\n"
            ."- Layout grids\n"
            ."- Spacing units\n"
            ."- Border radius\n"
            ."- Shadows & effects\n\n"
            ."## 4. UI Components\n"
            ."- Button styles\n"
            ."- Form elements\n"
            ."- Cards & containers\n"
            ."- Icons style\n\n"
            ."## 5. Imagery Guidelines\n"
            ."- Photography style\n"
            ."- Illustration style\n"
            ."- Image treatments\n\n"
            ."## 6. Do's and Don'ts\n"
            ."- Best practices\n"
            ."- Common mistakes to avoid\n\n"
            ."Format as a well-structured document with clear sections.";

        $result = $gemini->generateBrandGuidelines($assetPaths, [
            'task_type' => 'brand_guidelines_generation',
            'client_id' => $brandInfo['client_id'] ?? null,
            'temperature' => 0.4,
        ]);

        return [
            'guidelines' => $result['guidelines'],
            'brand_info' => $brandInfo,
            'assets_analyzed' => count($assetPaths),
            'tokens_used' => $result['tokens'],
            'cost' => $result['estimated_cost'],
        ];
    }

    /**
     * Compare design against brand guidelines for consistency.
     *
     * @param  string  $designPath  Path to design mockup
     * @param  array<string, mixed>  $guidelines  Brand guidelines
     * @return array<string, mixed>
     */
    public function auditDesignCompliance(string $designPath, array $guidelines): array
    {
        $gemini = $this->aiManager->provider('gemini');

        $guidelinesSummary = json_encode($guidelines, JSON_PRETTY_PRINT);

        $prompt = "Review this design for brand compliance.\n\n"
            ."Brand Guidelines:\n{$guidelinesSummary}\n\n"
            ."Analyze the design and provide:\n\n"
            ."1. **Compliance Score** (0-100)\n"
            ."2. **Color Usage**: Are brand colors used correctly?\n"
            ."3. **Typography**: Do fonts match guidelines?\n"
            ."4. **Layout**: Does spacing follow the design system?\n"
            ."5. **Logo Usage**: Is logo placement appropriate?\n"
            ."6. **Issues Found**: List any violations\n"
            ."7. **Recommendations**: How to fix issues\n\n"
            ."Format as JSON with sections.";

        $result = $gemini->analyzeImage($designPath, $prompt, [
            'task_type' => 'brand_compliance_audit',
        ]);

        $audit = $this->parseJsonResponse($result['text']);

        return [
            'raw_audit' => $result['text'],
            'structured_audit' => $audit,
            'compliance_score' => $audit['compliance_score'] ?? null,
            'tokens_used' => $result['tokens'],
            'cost' => $result['estimated_cost'],
        ];
    }

    /**
     * Extract color palette from image.
     *
     * @param  string  $imagePath
     * @return array<string, mixed>
     */
    public function extractColorPalette(string $imagePath): array
    {
        $gemini = $this->aiManager->provider('gemini');

        $prompt = "Extract the color palette from this image.\n\n"
            ."Provide:\n"
            ."1. All distinct colors with hex codes\n"
            ."2. Identify primary, secondary, and accent colors\n"
            ."3. Suggest color names (e.g., 'Navy Blue', 'Coral Pink')\n"
            ."4. Color harmony analysis (complementary, analogous, etc.)\n"
            ."5. Accessibility notes (WCAG contrast ratios)\n\n"
            ."Format as JSON with this structure:\n"
            ."{\n"
            ."  \"primary\": [{\"hex\": \"#000000\", \"name\": \"Black\"}],\n"
            ."  \"secondary\": [...],\n"
            ."  \"accents\": [...],\n"
            ."  \"harmony\": \"...\",\n"
            ."  \"accessibility\": {...}\n"
            ."}";

        $result = $gemini->analyzeImage($imagePath, $prompt, [
            'task_type' => 'color_palette_extraction',
            'temperature' => 0.2,
        ]);

        $palette = $this->parseJsonResponse($result['text']);

        return [
            'palette' => $palette,
            'raw_response' => $result['text'],
            'tokens_used' => $result['tokens'],
            'cost' => $result['estimated_cost'],
        ];
    }

    /**
     * Generate Tailwind CSS config from brand colors.
     *
     * @param  array<string, mixed>  $colorPalette
     * @return string
     */
    public function generateTailwindConfig(array $colorPalette): string
    {
        $gemini = $this->aiManager->provider('gemini');

        $paletteJson = json_encode($colorPalette, JSON_PRETTY_PRINT);

        $prompt = "Generate a Tailwind CSS configuration based on this color palette:\n\n"
            ."{$paletteJson}\n\n"
            ."Create a `tailwind.config.js` file that:\n"
            ."1. Extends the default Tailwind theme\n"
            ."2. Adds custom colors with appropriate shades (50-900)\n"
            ."3. Uses semantic naming (primary, secondary, accent, etc.)\n"
            ."4. Includes documentation comments\n\n"
            ."Provide only the JavaScript code, ready to use.";

        $result = $gemini->generateText($prompt, [
            'task_type' => 'tailwind_config_generation',
            'temperature' => 0.3,
        ]);

        return $result;
    }

    /**
     * Parse JSON response from AI, handling markdown code blocks.
     *
     * @param  string  $response
     * @return array<string, mixed>|null
     */
    protected function parseJsonResponse(string $response): ?array
    {
        // Try direct parse
        $decoded = json_decode($response, true);
        if ($decoded !== null) {
            return $decoded;
        }

        // Try extracting from markdown code block
        if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
            return json_decode($matches[1], true);
        }

        // Try extracting from any code block
        if (preg_match('/```\s*(.*?)\s*```/s', $response, $matches)) {
            return json_decode($matches[1], true);
        }

        return null;
    }
}
