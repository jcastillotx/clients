<?php

namespace App\Services\SEO;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google PageSpeed Insights API Service
 *
 * FREE API with generous limits (25,000 queries/day).
 * Features: Core Web Vitals, Lighthouse scores, performance metrics
 *
 * @see https://developers.google.com/speed/docs/insights/v5/get-started
 */
class GooglePageSpeedService
{
    protected string $endpoint = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    protected ?string $apiKey;

    protected bool $enabled;

    public function __construct()
    {
        $this->apiKey = config('seo.free.google_pagespeed.api_key') ?: app('settings')->get('api.seo.google_pagespeed.api_key');
        $this->enabled = (bool) (config('seo.free.google_pagespeed.enabled') ?? app('settings')->get('api.seo.google_pagespeed.enabled', true));
    }

    public function isConfigured(): bool
    {
        // API works without key (with limits), so we just check if enabled
        return $this->enabled;
    }

    /**
     * Run PageSpeed analysis on a URL
     *
     * @param  string  $strategy  'desktop' or 'mobile'
     * @param  array<string>  $categories  ['performance', 'accessibility', 'best-practices', 'seo', 'pwa']
     * @return array<string, mixed>
     */
    public function analyze(string $url, string $strategy = 'mobile', array $categories = ['performance', 'seo']): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'PageSpeed not enabled', 'data' => []];
        }

        $cacheKey = 'pagespeed:'.md5($url.$strategy.implode(',', $categories));

        return Cache::remember($cacheKey, config('seo.cache.page_speed_ttl', 86400), function () use ($url, $strategy, $categories) {
            try {
                $params = [
                    'url' => $url,
                    'strategy' => $strategy,
                    'category' => $categories,
                ];

                if (! empty($this->apiKey)) {
                    $params['key'] = $this->apiKey;
                }

                $response = Http::timeout(60)
                    ->get($this->endpoint, $params);

                if ($response->successful()) {
                    $data = $response->json();
                    $lighthouseResult = $data['lighthouseResult'] ?? [];

                    return [
                        'success' => true,
                        'url' => $url,
                        'strategy' => $strategy,
                        'scores' => $this->extractScores($lighthouseResult),
                        'core_web_vitals' => $this->extractCoreWebVitals($lighthouseResult),
                        'opportunities' => $this->extractOpportunities($lighthouseResult),
                        'diagnostics' => $this->extractDiagnostics($lighthouseResult),
                        'fetch_time' => $data['analysisUTCTimestamp'] ?? null,
                    ];
                }

                return ['error' => 'API request failed: '.($response->json('error.message') ?? $response->status()), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('PageSpeed analysis error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Quick performance check (performance only)
     *
     * @return array<string, mixed>
     */
    public function quickCheck(string $url, string $strategy = 'mobile'): array
    {
        return $this->analyze($url, $strategy, ['performance']);
    }

    /**
     * Full SEO and performance audit
     *
     * @return array<string, mixed>
     */
    public function fullAudit(string $url): array
    {
        $mobile = $this->analyze($url, 'mobile', ['performance', 'accessibility', 'best-practices', 'seo']);
        $desktop = $this->analyze($url, 'desktop', ['performance', 'accessibility', 'best-practices', 'seo']);

        return [
            'success' => ($mobile['success'] ?? false) && ($desktop['success'] ?? false),
            'url' => $url,
            'mobile' => $mobile,
            'desktop' => $desktop,
            'comparison' => [
                'performance_diff' => ($desktop['scores']['performance'] ?? 0) - ($mobile['scores']['performance'] ?? 0),
                'seo_diff' => ($desktop['scores']['seo'] ?? 0) - ($mobile['scores']['seo'] ?? 0),
            ],
        ];
    }

    /**
     * Extract category scores (0-100)
     *
     * @return array<string, int|null>
     */
    protected function extractScores(array $lighthouseResult): array
    {
        $categories = $lighthouseResult['categories'] ?? [];

        return [
            'performance' => isset($categories['performance']['score']) ? (int) ($categories['performance']['score'] * 100) : null,
            'accessibility' => isset($categories['accessibility']['score']) ? (int) ($categories['accessibility']['score'] * 100) : null,
            'best_practices' => isset($categories['best-practices']['score']) ? (int) ($categories['best-practices']['score'] * 100) : null,
            'seo' => isset($categories['seo']['score']) ? (int) ($categories['seo']['score'] * 100) : null,
            'pwa' => isset($categories['pwa']['score']) ? (int) ($categories['pwa']['score'] * 100) : null,
        ];
    }

    /**
     * Extract Core Web Vitals
     *
     * @return array<string, mixed>
     */
    protected function extractCoreWebVitals(array $lighthouseResult): array
    {
        $audits = $lighthouseResult['audits'] ?? [];

        return [
            'lcp' => [
                'value' => $audits['largest-contentful-paint']['numericValue'] ?? null,
                'display' => $audits['largest-contentful-paint']['displayValue'] ?? null,
                'score' => isset($audits['largest-contentful-paint']['score']) ? (int) ($audits['largest-contentful-paint']['score'] * 100) : null,
            ],
            'fid' => [
                'value' => $audits['max-potential-fid']['numericValue'] ?? null,
                'display' => $audits['max-potential-fid']['displayValue'] ?? null,
                'score' => isset($audits['max-potential-fid']['score']) ? (int) ($audits['max-potential-fid']['score'] * 100) : null,
            ],
            'cls' => [
                'value' => $audits['cumulative-layout-shift']['numericValue'] ?? null,
                'display' => $audits['cumulative-layout-shift']['displayValue'] ?? null,
                'score' => isset($audits['cumulative-layout-shift']['score']) ? (int) ($audits['cumulative-layout-shift']['score'] * 100) : null,
            ],
            'fcp' => [
                'value' => $audits['first-contentful-paint']['numericValue'] ?? null,
                'display' => $audits['first-contentful-paint']['displayValue'] ?? null,
                'score' => isset($audits['first-contentful-paint']['score']) ? (int) ($audits['first-contentful-paint']['score'] * 100) : null,
            ],
            'ttfb' => [
                'value' => $audits['server-response-time']['numericValue'] ?? null,
                'display' => $audits['server-response-time']['displayValue'] ?? null,
                'score' => isset($audits['server-response-time']['score']) ? (int) ($audits['server-response-time']['score'] * 100) : null,
            ],
            'tbt' => [
                'value' => $audits['total-blocking-time']['numericValue'] ?? null,
                'display' => $audits['total-blocking-time']['displayValue'] ?? null,
                'score' => isset($audits['total-blocking-time']['score']) ? (int) ($audits['total-blocking-time']['score'] * 100) : null,
            ],
            'speed_index' => [
                'value' => $audits['speed-index']['numericValue'] ?? null,
                'display' => $audits['speed-index']['displayValue'] ?? null,
                'score' => isset($audits['speed-index']['score']) ? (int) ($audits['speed-index']['score'] * 100) : null,
            ],
        ];
    }

    /**
     * Extract performance opportunities
     *
     * @return array<int, array<string, mixed>>
     */
    protected function extractOpportunities(array $lighthouseResult): array
    {
        $audits = $lighthouseResult['audits'] ?? [];
        $opportunities = [];

        $opportunityKeys = [
            'render-blocking-resources',
            'unused-css-rules',
            'unused-javascript',
            'modern-image-formats',
            'uses-optimized-images',
            'uses-responsive-images',
            'offscreen-images',
            'efficient-animated-content',
            'uses-text-compression',
            'uses-rel-preconnect',
            'server-response-time',
            'redirects',
            'preload-lcp-image',
            'unminified-css',
            'unminified-javascript',
        ];

        foreach ($opportunityKeys as $key) {
            if (isset($audits[$key]) && ($audits[$key]['score'] ?? 1) < 1) {
                $opportunities[] = [
                    'id' => $key,
                    'title' => $audits[$key]['title'] ?? $key,
                    'description' => $audits[$key]['description'] ?? null,
                    'savings' => $audits[$key]['numericValue'] ?? null,
                    'display' => $audits[$key]['displayValue'] ?? null,
                ];
            }
        }

        return $opportunities;
    }

    /**
     * Extract diagnostic information
     *
     * @return array<int, array<string, mixed>>
     */
    protected function extractDiagnostics(array $lighthouseResult): array
    {
        $audits = $lighthouseResult['audits'] ?? [];
        $diagnostics = [];

        $diagnosticKeys = [
            'dom-size',
            'critical-request-chains',
            'main-thread-work-breakdown',
            'bootup-time',
            'uses-long-cache-ttl',
            'total-byte-weight',
            'third-party-summary',
            'largest-contentful-paint-element',
            'layout-shift-elements',
            'long-tasks',
        ];

        foreach ($diagnosticKeys as $key) {
            if (isset($audits[$key])) {
                $diagnostics[] = [
                    'id' => $key,
                    'title' => $audits[$key]['title'] ?? $key,
                    'description' => $audits[$key]['description'] ?? null,
                    'display' => $audits[$key]['displayValue'] ?? null,
                    'score' => isset($audits[$key]['score']) ? (int) ($audits[$key]['score'] * 100) : null,
                ];
            }
        }

        return $diagnostics;
    }

    /**
     * Test API connection
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'PageSpeed not enabled'];
        }

        try {
            $params = [
                'url' => 'https://www.google.com',
                'strategy' => 'desktop',
                'category' => ['performance'],
            ];

            if (! empty($this->apiKey)) {
                $params['key'] = $this->apiKey;
            }

            $response = Http::timeout(30)
                ->get($this->endpoint, $params);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Connected successfully'];
            }

            return ['success' => false, 'message' => 'Connection failed: '.($response->json('error.message') ?? $response->status())];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Error: '.$e->getMessage()];
        }
    }
}
