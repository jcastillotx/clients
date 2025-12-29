<?php

namespace App\Services\SEO;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ahrefs API Service
 *
 * Premium SEO data provider (from $99/month for tool, API from $399/month).
 * Features: Domain Rating, URL Rating, Backlinks, Organic Keywords, Site Audit
 *
 * @see https://docs.ahrefs.com/docs/api
 */
class AhrefsService
{
    protected string $endpoint = 'https://api.ahrefs.com/v3';

    protected ?string $apiKey;

    protected bool $enabled;

    public function __construct()
    {
        $this->apiKey = config('seo.commercial.ahrefs.api_key') ?: app('settings')->get('api.seo.ahrefs.api_key');
        $this->enabled = (bool) (config('seo.commercial.ahrefs.enabled') ?? app('settings')->get('api.seo.ahrefs.enabled', false));
    }

    public function isConfigured(): bool
    {
        return $this->enabled && ! empty($this->apiKey);
    }

    /**
     * Get domain rating and metrics
     *
     * @return array<string, mixed>
     */
    public function getDomainMetrics(string $domain): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Ahrefs not configured', 'data' => []];
        }

        $cacheKey = 'ahrefs:domain:'.md5($domain);

        return Cache::remember($cacheKey, config('seo.cache.domain_metrics_ttl', 86400), function () use ($domain) {
            try {
                $response = Http::withToken($this->apiKey)
                    ->timeout(30)
                    ->get("{$this->endpoint}/site-explorer/domain-rating", [
                        'target' => $domain,
                        'date' => now()->format('Y-m-d'),
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'success' => true,
                        'domain' => $domain,
                        'domain_rating' => $data['domain_rating'] ?? null,
                        'ahrefs_rank' => $data['ahrefs_rank'] ?? null,
                    ];
                }

                return ['error' => 'API request failed: '.$response->status(), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('Ahrefs domain metrics error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get URL rating
     *
     * @return array<string, mixed>
     */
    public function getUrlRating(string $url): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Ahrefs not configured', 'data' => []];
        }

        $cacheKey = 'ahrefs:url_rating:'.md5($url);

        return Cache::remember($cacheKey, config('seo.cache.domain_metrics_ttl', 86400), function () use ($url) {
            try {
                $response = Http::withToken($this->apiKey)
                    ->timeout(30)
                    ->get("{$this->endpoint}/site-explorer/url-rating", [
                        'target' => $url,
                        'date' => now()->format('Y-m-d'),
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'success' => true,
                        'url' => $url,
                        'url_rating' => $data['url_rating'] ?? null,
                    ];
                }

                return ['error' => 'API request failed: '.$response->status(), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('Ahrefs URL rating error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get backlinks overview
     *
     * @return array<string, mixed>
     */
    public function getBacklinksOverview(string $target): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Ahrefs not configured', 'data' => []];
        }

        $cacheKey = 'ahrefs:backlinks_overview:'.md5($target);

        return Cache::remember($cacheKey, config('seo.cache.backlink_data_ttl', 604800), function () use ($target) {
            try {
                $response = Http::withToken($this->apiKey)
                    ->timeout(30)
                    ->get("{$this->endpoint}/site-explorer/metrics", [
                        'target' => $target,
                        'mode' => 'domain',
                        'date' => now()->format('Y-m-d'),
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'success' => true,
                        'target' => $target,
                        'total_backlinks' => $data['backlinks'] ?? null,
                        'referring_domains' => $data['refdomains'] ?? null,
                        'dofollow_backlinks' => $data['dofollow_backlinks'] ?? null,
                        'organic_keywords' => $data['organic_keywords'] ?? null,
                        'organic_traffic' => $data['organic_traffic'] ?? null,
                        'traffic_value' => $data['traffic_value'] ?? null,
                    ];
                }

                return ['error' => 'API request failed: '.$response->status(), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('Ahrefs backlinks overview error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get top backlinks
     *
     * @return array<string, mixed>
     */
    public function getTopBacklinks(string $target, int $limit = 100): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Ahrefs not configured', 'data' => []];
        }

        $cacheKey = 'ahrefs:top_backlinks:'.md5($target.$limit);

        return Cache::remember($cacheKey, config('seo.cache.backlink_data_ttl', 604800), function () use ($target, $limit) {
            try {
                $response = Http::withToken($this->apiKey)
                    ->timeout(60)
                    ->get("{$this->endpoint}/site-explorer/all-backlinks", [
                        'target' => $target,
                        'mode' => 'domain',
                        'limit' => $limit,
                        'order_by' => 'domain_rating_source:desc',
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'success' => true,
                        'target' => $target,
                        'backlinks' => $data['backlinks'] ?? [],
                    ];
                }

                return ['error' => 'API request failed: '.$response->status(), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('Ahrefs top backlinks error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get organic keywords for a domain
     *
     * @return array<string, mixed>
     */
    public function getOrganicKeywords(string $target, string $country = 'us', int $limit = 100): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Ahrefs not configured', 'data' => []];
        }

        $cacheKey = 'ahrefs:organic_keywords:'.md5($target.$country.$limit);

        return Cache::remember($cacheKey, config('seo.cache.keyword_data_ttl', 86400), function () use ($target, $country, $limit) {
            try {
                $response = Http::withToken($this->apiKey)
                    ->timeout(60)
                    ->get("{$this->endpoint}/site-explorer/organic-keywords", [
                        'target' => $target,
                        'mode' => 'domain',
                        'country' => $country,
                        'limit' => $limit,
                        'order_by' => 'volume:desc',
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'success' => true,
                        'target' => $target,
                        'keywords' => collect($data['keywords'] ?? [])->map(function ($kw) {
                            return [
                                'keyword' => $kw['keyword'] ?? null,
                                'volume' => $kw['volume'] ?? null,
                                'position' => $kw['position'] ?? null,
                                'traffic' => $kw['traffic'] ?? null,
                                'url' => $kw['url'] ?? null,
                                'difficulty' => $kw['keyword_difficulty'] ?? null,
                            ];
                        })->toArray(),
                    ];
                }

                return ['error' => 'API request failed: '.$response->status(), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('Ahrefs organic keywords error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get referring domains
     *
     * @return array<string, mixed>
     */
    public function getReferringDomains(string $target, int $limit = 100): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Ahrefs not configured', 'data' => []];
        }

        $cacheKey = 'ahrefs:referring_domains:'.md5($target.$limit);

        return Cache::remember($cacheKey, config('seo.cache.backlink_data_ttl', 604800), function () use ($target, $limit) {
            try {
                $response = Http::withToken($this->apiKey)
                    ->timeout(60)
                    ->get("{$this->endpoint}/site-explorer/refdomains", [
                        'target' => $target,
                        'mode' => 'domain',
                        'limit' => $limit,
                        'order_by' => 'domain_rating:desc',
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'success' => true,
                        'target' => $target,
                        'referring_domains' => $data['refdomains'] ?? [],
                    ];
                }

                return ['error' => 'API request failed: '.$response->status(), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('Ahrefs referring domains error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Test API connection
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['success' => false, 'message' => 'Ahrefs not configured'];
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(10)
                ->get("{$this->endpoint}/subscription-info");

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Connected successfully'];
            }

            return ['success' => false, 'message' => 'Connection failed: '.($response->json('error') ?? $response->status())];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Error: '.$e->getMessage()];
        }
    }
}
