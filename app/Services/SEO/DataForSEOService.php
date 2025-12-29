<?php

namespace App\Services\SEO;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DataForSEO API Service
 *
 * Low-cost SEO data provider with pay-per-use pricing (from $50/month).
 * Features: SERP tracking, keyword data, backlinks, on-page SEO, content analysis
 *
 * @see https://docs.dataforseo.com/
 */
class DataForSEOService
{
    protected string $endpoint = 'https://api.dataforseo.com/v3';

    protected ?string $login;

    protected ?string $password;

    protected bool $enabled;

    public function __construct()
    {
        $this->login = config('seo.low_cost.dataforseo.login') ?: app('settings')->get('api.seo.dataforseo.login');
        $this->password = config('seo.low_cost.dataforseo.password') ?: app('settings')->get('api.seo.dataforseo.password');
        $this->enabled = (bool) (config('seo.low_cost.dataforseo.enabled') ?? app('settings')->get('api.seo.dataforseo.enabled', false));
    }

    public function isConfigured(): bool
    {
        return $this->enabled && ! empty($this->login) && ! empty($this->password);
    }

    /**
     * Get keyword search volume, CPC, and competition data
     *
     * @param  array<string>  $keywords
     * @return array<string, mixed>
     */
    public function getKeywordData(array $keywords, string $location = 'United States', string $language = 'en'): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'DataForSEO not configured', 'data' => []];
        }

        $cacheKey = 'dataforseo:keywords:'.md5(implode(',', $keywords).$location.$language);

        return Cache::remember($cacheKey, config('seo.cache.keyword_data_ttl', 86400), function () use ($keywords, $location, $language) {
            try {
                $response = Http::withBasicAuth($this->login, $this->password)
                    ->timeout(30)
                    ->post("{$this->endpoint}/keywords_data/google_ads/search_volume/live", [
                        [
                            'keywords' => array_slice($keywords, 0, 1000), // API limit
                            'location_name' => $location,
                            'language_name' => $language,
                        ],
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (($data['status_code'] ?? 0) === 20000) {
                        return [
                            'success' => true,
                            'data' => $data['tasks'][0]['result'] ?? [],
                            'cost' => $data['cost'] ?? null,
                        ];
                    }

                    return ['error' => $data['status_message'] ?? 'Unknown error', 'data' => []];
                }

                return ['error' => 'API request failed: '.$response->status(), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('DataForSEO keyword data error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get SERP results for a keyword
     *
     * @return array<string, mixed>
     */
    public function getSerpResults(string $keyword, string $location = 'United States', string $device = 'desktop', int $depth = 100): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'DataForSEO not configured', 'data' => []];
        }

        $cacheKey = 'dataforseo:serp:'.md5($keyword.$location.$device.$depth);

        return Cache::remember($cacheKey, config('seo.cache.serp_data_ttl', 3600), function () use ($keyword, $location, $device, $depth) {
            try {
                $response = Http::withBasicAuth($this->login, $this->password)
                    ->timeout(60)
                    ->post("{$this->endpoint}/serp/google/organic/live/regular", [
                        [
                            'keyword' => $keyword,
                            'location_name' => $location,
                            'device' => $device,
                            'depth' => $depth,
                        ],
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (($data['status_code'] ?? 0) === 20000) {
                        $results = $data['tasks'][0]['result'][0] ?? [];

                        return [
                            'success' => true,
                            'keyword' => $keyword,
                            'items' => $results['items'] ?? [],
                            'total_count' => $results['se_results_count'] ?? 0,
                            'cost' => $data['cost'] ?? null,
                        ];
                    }

                    return ['error' => $data['status_message'] ?? 'Unknown error', 'data' => []];
                }

                return ['error' => 'API request failed: '.$response->status(), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('DataForSEO SERP error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get backlinks for a domain
     *
     * @return array<string, mixed>
     */
    public function getBacklinks(string $target, int $limit = 100): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'DataForSEO not configured', 'data' => []];
        }

        $cacheKey = 'dataforseo:backlinks:'.md5($target.$limit);

        return Cache::remember($cacheKey, config('seo.cache.backlink_data_ttl', 604800), function () use ($target, $limit) {
            try {
                $response = Http::withBasicAuth($this->login, $this->password)
                    ->timeout(60)
                    ->post("{$this->endpoint}/backlinks/backlinks/live", [
                        [
                            'target' => $target,
                            'limit' => $limit,
                            'mode' => 'as_is',
                        ],
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (($data['status_code'] ?? 0) === 20000) {
                        return [
                            'success' => true,
                            'target' => $target,
                            'backlinks' => $data['tasks'][0]['result'][0]['items'] ?? [],
                            'total_count' => $data['tasks'][0]['result'][0]['total_count'] ?? 0,
                            'cost' => $data['cost'] ?? null,
                        ];
                    }

                    return ['error' => $data['status_message'] ?? 'Unknown error', 'data' => []];
                }

                return ['error' => 'API request failed: '.$response->status(), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('DataForSEO backlinks error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get domain metrics (rank, backlinks count, referring domains)
     *
     * @return array<string, mixed>
     */
    public function getDomainMetrics(string $domain): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'DataForSEO not configured', 'data' => []];
        }

        $cacheKey = 'dataforseo:domain:'.md5($domain);

        return Cache::remember($cacheKey, config('seo.cache.domain_metrics_ttl', 86400), function () use ($domain) {
            try {
                $response = Http::withBasicAuth($this->login, $this->password)
                    ->timeout(30)
                    ->post("{$this->endpoint}/backlinks/summary/live", [
                        [
                            'target' => $domain,
                        ],
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (($data['status_code'] ?? 0) === 20000) {
                        $result = $data['tasks'][0]['result'][0] ?? [];

                        return [
                            'success' => true,
                            'domain' => $domain,
                            'rank' => $result['rank'] ?? null,
                            'backlinks' => $result['backlinks'] ?? 0,
                            'referring_domains' => $result['referring_domains'] ?? 0,
                            'referring_ips' => $result['referring_ips'] ?? 0,
                            'dofollow' => $result['backlinks_nofollow'] ?? 0,
                            'nofollow' => $result['backlinks_nofollow'] ?? 0,
                            'cost' => $data['cost'] ?? null,
                        ];
                    }

                    return ['error' => $data['status_message'] ?? 'Unknown error', 'data' => []];
                }

                return ['error' => 'API request failed: '.$response->status(), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('DataForSEO domain metrics error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get on-page SEO audit for a URL
     *
     * @return array<string, mixed>
     */
    public function getOnPageAudit(string $url): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'DataForSEO not configured', 'data' => []];
        }

        try {
            $response = Http::withBasicAuth($this->login, $this->password)
                ->timeout(60)
                ->post("{$this->endpoint}/on_page/instant_pages", [
                    [
                        'url' => $url,
                        'enable_javascript' => true,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (($data['status_code'] ?? 0) === 20000) {
                    return [
                        'success' => true,
                        'url' => $url,
                        'audit' => $data['tasks'][0]['result'][0] ?? [],
                        'cost' => $data['cost'] ?? null,
                    ];
                }

                return ['error' => $data['status_message'] ?? 'Unknown error', 'data' => []];
            }

            return ['error' => 'API request failed: '.$response->status(), 'data' => []];
        } catch (\Throwable $e) {
            Log::error('DataForSEO on-page audit error', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Test API connection
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'DataForSEO not configured'];
        }

        try {
            $response = Http::withBasicAuth($this->login, $this->password)
                ->timeout(10)
                ->get("{$this->endpoint}/serp/google/organic/locations");

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Connected successfully'];
            }

            return ['success' => false, 'message' => 'Connection failed: '.$response->status()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Error: '.$e->getMessage()];
        }
    }
}
