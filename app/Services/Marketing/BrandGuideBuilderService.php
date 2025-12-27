<?php

namespace App\Services\Marketing;

use App\Models\BrandAsset;
use App\Models\BrandColor;
use App\Models\BrandFont;
use App\Models\BrandGuide;
use App\Models\BrandGuideSection;
use App\Models\BrandTemplate;
use App\Models\Client;
use App\Services\AI\AIProviderManager;
use Illuminate\Support\Str;

class BrandGuideBuilderService
{
    public function __construct(private readonly AIProviderManager $ai)
    {
    }

    /**
     * Generate/refresh a brand guide for a client.
     *
     * @return array{guide:BrandGuide, sections:int, colors:int, fonts:int, templates:int}
     */
    public function generateBrandGuide(Client $client, array $options = []): array
    {
        $elements = $this->extractBrandElements($client);

        $slug = (string) ($options['slug'] ?? Str::slug($client->company_name ?: ('client-' . $client->id)) . '-' . Str::lower(Str::random(6)));

        $guide = BrandGuide::create([
            'client_id' => $client->id,
            'version' => 1,
            'status' => 'draft',
            'slug' => $slug,
            'is_public' => (bool) ($options['is_public'] ?? false),
            'password_protected' => (bool) ($options['password_protected'] ?? false),
            'password' => isset($options['password']) ? bcrypt((string) $options['password']) : null,
            'created_by' => $options['created_by'] ?? null,
            'meta' => [
                'generated_at' => now()->toIso8601String(),
            ],
        ]);

        // Sections scaffold (structured JSON, editable later)
        $sections = $this->defaultSectionsFromElements($client, $elements);
        $order = 1;
        foreach ($sections as $s) {
            BrandGuideSection::create([
                'brand_guide_id' => $guide->id,
                'section_type' => (string) ($s['section_type'] ?? 'custom'),
                'section_order' => $order++,
                'title' => (string) ($s['title'] ?? ''),
                'content' => is_array($s['content'] ?? null) ? (array) $s['content'] : null,
                'is_visible' => true,
            ]);
        }

        foreach ((array) ($elements['colors'] ?? []) as $c) {
            if (!is_array($c)) continue;
            BrandColor::create([
                'brand_guide_id' => $guide->id,
                'color_name' => $c['name'] ?? null,
                'color_type' => $c['type'] ?? 'primary',
                'hex_value' => $c['hex'] ?? null,
                'rgb_value' => $c['rgb'] ?? null,
                'cmyk_value' => $c['cmyk'] ?? null,
                'pantone_value' => $c['pantone'] ?? null,
                'usage_context' => $c['usage'] ?? null,
                'accessibility_notes' => $c['accessibility_notes'] ?? null,
            ]);
        }

        foreach ((array) ($elements['fonts'] ?? []) as $f) {
            if (!is_array($f)) continue;
            BrandFont::create([
                'brand_guide_id' => $guide->id,
                'font_name' => (string) ($f['name'] ?? 'Unknown font'),
                'font_category' => $f['category'] ?? 'primary',
                'font_weights' => $f['weights'] ?? null,
                'web_font_url' => $f['web_font_url'] ?? null,
                'usage_context' => $f['usage'] ?? null,
                'licensing_info' => $f['licensing'] ?? null,
            ]);
        }

        foreach ((array) ($elements['templates'] ?? []) as $t) {
            if (!is_array($t)) continue;
            if (empty($t['file_path'])) continue;
            BrandTemplate::create([
                'brand_guide_id' => $guide->id,
                'template_name' => (string) ($t['name'] ?? 'Template'),
                'template_type' => (string) ($t['type'] ?? 'generic'),
                'file_path' => (string) $t['file_path'],
                'thumbnail' => $t['thumbnail'] ?? null,
                'is_public' => (bool) ($t['is_public'] ?? false),
                'meta' => $t['meta'] ?? null,
            ]);
        }

        return [
            'guide' => $guide,
            'sections' => $guide->sections()->count(),
            'colors' => $guide->colors()->count(),
            'fonts' => $guide->fonts()->count(),
            'templates' => $guide->templates()->count(),
        ];
    }

    /**
     * Auto-detect brand elements from existing materials (best-effort).
     *
     * @return array<string,mixed>
     */
    public function extractBrandElements(Client $client): array
    {
        $approvedAssets = BrandAsset::query()
            ->where('client_id', $client->id)
            ->where('is_approved', true)
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn ($a) => $a->only(['asset_type', 'asset_name', 'asset_value', 'usage_context']))
            ->all();

        $payload = [
            'client' => [
                'company_name' => $client->company_name,
                'website' => $client->website,
                'industry' => $client->industry,
            ],
            'known_assets' => $approvedAssets,
            'task' => 'Organize brand elements into JSON keys: colors[], fonts[], logos[], imagery_guidelines, voice_tone, templates[]. Suggest missing elements.',
        ];

        $res = $this->ai->withFallback('claude', function ($provider) use ($payload) {
            return $provider->chat([
                ['role' => 'system', 'content' => 'Output strict JSON only.'],
                ['role' => 'user', 'content' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}'],
            ], [
                'task_type' => 'brand_guide_extract_elements',
                'timeout' => 90,
                'max_tokens' => 1200,
            ]);
        }, taskType: 'brand_guide_extract_elements');

        return $this->tryParseJson((string) ($res['text'] ?? '')) ?? [
            'colors' => [],
            'fonts' => [],
            'logos' => [],
            'templates' => [],
            'raw' => (string) ($res['text'] ?? ''),
        ];
    }

    /**
     * @param array<string,mixed> $elements
     * @return array<int,array{section_type:string,title:string,content:array<string,mixed>}>
     */
    protected function defaultSectionsFromElements(Client $client, array $elements): array
    {
        return [
            [
                'section_type' => 'story',
                'title' => 'Brand story & foundation',
                'content' => [
                    'company_name' => $client->company_name,
                    'mission' => data_get($elements, 'foundation.mission'),
                    'vision' => data_get($elements, 'foundation.vision'),
                    'values' => data_get($elements, 'foundation.values'),
                    'positioning' => data_get($elements, 'foundation.positioning'),
                ],
            ],
            [
                'section_type' => 'logo',
                'title' => 'Logo & visual identity',
                'content' => [
                    'logos' => (array) data_get($elements, 'logos', []),
                    'usage_rules' => data_get($elements, 'logo_guidelines'),
                ],
            ],
            [
                'section_type' => 'colors',
                'title' => 'Color palette',
                'content' => [
                    'notes' => data_get($elements, 'color_guidelines'),
                ],
            ],
            [
                'section_type' => 'typography',
                'title' => 'Typography',
                'content' => [
                    'notes' => data_get($elements, 'typography_guidelines'),
                ],
            ],
            [
                'section_type' => 'imagery',
                'title' => 'Imagery & photography',
                'content' => [
                    'guidelines' => data_get($elements, 'imagery_guidelines'),
                ],
            ],
            [
                'section_type' => 'voice',
                'title' => 'Voice & tone',
                'content' => [
                    'voice_tone' => data_get($elements, 'voice_tone'),
                    'examples' => data_get($elements, 'voice_examples'),
                ],
            ],
            [
                'section_type' => 'digital',
                'title' => 'Digital applications',
                'content' => [
                    'guidelines' => data_get($elements, 'digital_guidelines'),
                ],
            ],
            [
                'section_type' => 'print',
                'title' => 'Print applications',
                'content' => [
                    'guidelines' => data_get($elements, 'print_guidelines'),
                ],
            ],
            [
                'section_type' => 'social',
                'title' => 'Social media',
                'content' => [
                    'content_pillars' => data_get($elements, 'social.content_pillars'),
                    'guidelines' => data_get($elements, 'social.guidelines'),
                ],
            ],
            [
                'section_type' => 'elements',
                'title' => 'Brand elements',
                'content' => [
                    'patterns' => data_get($elements, 'elements.patterns'),
                    'icons' => data_get($elements, 'elements.icons'),
                    'templates' => (array) data_get($elements, 'templates', []),
                ],
            ],
        ];
    }

    protected function tryParseJson(string $text): ?array
    {
        $text = trim($text);
        if ($text === '') return null;
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
        $text = preg_replace('/\s*```$/', '', $text) ?? $text;
        $decoded = json_decode($text, true);
        return is_array($decoded) ? $decoded : null;
    }
}

