<?php

namespace App\Services\SEO;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Moz API Service
 *
 * Commercial SEO data provider (from $99/month).
 * Features: Domain Authority, Page Authority, Spam Score, Link Metrics
 *
 * @see https://moz.com/products/api
 */
class MozService
{
    protected string $endpoint = 'https://lsapi.seomoz.com/v2';

    protected ?string $accessId;

    protected ?string $secretKey;

    protected bool $enabled;

    public function __construct()
    {
        $this->accessId = config('seo.commercial.moz.access_id') ?: app('settings')->get('api.seo.moz.access_id');
        $this->secretKey = config('seo.commercial.moz.secret_key') ?: app('settings')->get('api.seo.moz.secret_key');
        $this->enabled = (bool) (config('seo.commercial.moz.enabled') ?? app('settings')->get('api.seo.moz.enabled', false));
    }

    public function isConfigured(): bool
    {
        return $this->enabled && ! empty($this->accessId) && ! empty($this->secretKey);
    }

    /**
     * Get URL metrics (Domain Authority, Page Authority, Spam Score)
     *
     * @param  array<string>  $targets  URLs or domains
     * @return array<string, mixed>
     */
    public function getUrlMetrics(array $targets): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Moz not configured', 'data' => []];
        }

        $cacheKey = 'moz:metrics:'.md5(implode(',', $targets));

        return Cache::remember($cacheKey, config('seo.cache.domain_metrics_ttl', 86400), function () use ($targets) {
            try {
                $response = Http::withBasicAuth($this->accessId, $this->secretKey)
                    ->timeout(30)
                    ->post("{$this->endpoint}/url_metrics", [
                        'targets' => array_slice($targets, 0, 50), // API limit
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'success' => true,
                        'results' => collect($data['results'] ?? [])->map(function ($item) {
                            return [
                                'page' => $item['page'] ?? null,
                                'domain_authority' => $item['domain_authority'] ?? null,
                                'page_authority' => $item['page_authority'] ?? null,
                                'spam_score' => $item['spam_score'] ?? null,
                                'links_to_page' => $item['links_to_page'] ?? null,
                                'links_to_domain' => $item['links_to_domain'] ?? null,
                                'external_links_to_page' => $item['external_links_to_page'] ?? null,
                                'external_links_to_domain' => $item['external_links_to_domain'] ?? null,
                                'linking_root_domains' => $item['root_domains_to_page'] ?? null,
                                'linking_root_domains_to_root_domain' => $item['root_domains_to_root_domain'] ?? null,
                            ];
                        })->toArray(),
                    ];
                }

                return ['error' => 'API request failed: '.$response->status(), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('Moz URL metrics error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get Domain Authority for a single domain
     *
     * @return array<string, mixed>
     */
    public function getDomainAuthority(string $domain): array
    {
        $result = $this->getUrlMetrics([$domain]);

        if (isset($result['error'])) {
            return $result;
        }

        $metrics = $result['results'][0] ?? null;

        return [
            'success' => true,
            'domain' => $domain,
            'domain_authority' => $metrics['domain_authority'] ?? null,
            'page_authority' => $metrics['page_authority'] ?? null,
            'spam_score' => $metrics['spam_score'] ?? null,
            'linking_root_domains' => $metrics['linking_root_domains_to_root_domain'] ?? null,
            'external_links' => $metrics['external_links_to_domain'] ?? null,
        ];
    }

    /**
     * Get link metrics for a target
     *
     * @return array<string, mixed>
     */
    public function getLinkingDomains(string $target, int $limit = 50): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Moz not configured', 'data' => []];
        }

        $cacheKey = 'moz:linking_domains:'.md5($target.$limit);

        return Cache::remember($cacheKey, config('seo.cache.backlink_data_ttl', 604800), function () use ($target, $limit) {
            try {
                $response = Http::withBasicAuth($this->accessId, $this->secretKey)
                    ->timeout(60)
                    ->post("{$this->endpoint}/linking_root_domains", [
                        'target' => $target,
                        'limit' => $limit,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'success' => true,
                        'target' => $target,
                        'linking_domains' => $data['results'] ?? [],
                        'total_count' => $data['total_pages'] ?? 0,
                    ];
                }

                return ['error' => 'API request failed: '.$response->status(), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('Moz linking domains error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get top links to a target
     *
     * @return array<string, mixed>
     */
    public function getTopLinks(string $target, int $limit = 50): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Moz not configured', 'data' => []];
        }

        $cacheKey = 'moz:top_links:'.md5($target.$limit);

        return Cache::remember($cacheKey, config('seo.cache.backlink_data_ttl', 604800), function () use ($target, $limit) {
            try {
                $response = Http::withBasicAuth($this->accessId, $this->secretKey)
                    ->timeout(60)
                    ->post("{$this->endpoint}/links", [
                        'target' => $target,
                        'limit' => $limit,
                        'target_scope' => 'page',
                        'filter' => 'external+nofollow',
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'success' => true,
                        'target' => $target,
                        'links' => $data['results'] ?? [],
                    ];
                }

                return ['error' => 'API request failed: '.$response->status(), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('Moz top links error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get anchor text distribution
     *
     * @return array<string, mixed>
     */
    public function getAnchorText(string $target, int $limit = 50): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Moz not configured', 'data' => []];
        }

        $cacheKey = 'moz:anchor_text:'.md5($target.$limit);

        return Cache::remember($cacheKey, config('seo.cache.backlink_data_ttl', 604800), function () use ($target, $limit) {
            try {
                $response = Http::withBasicAuth($this->accessId, $this->secretKey)
                    ->timeout(60)
                    ->post("{$this->endpoint}/anchor_text", [
                        'target' => $target,
                        'limit' => $limit,
                        'scope' => 'page',
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'success' => true,
                        'target' => $target,
                        'anchor_texts' => $data['results'] ?? [],
                    ];
                }

                return ['error' => 'API request failed: '.$response->status(), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('Moz anchor text error', ['error' => $e->getMessage()]);

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
            return ['success' => false, 'message' => 'Moz not configured'];
        }

        try {
            $response = Http::withBasicAuth($this->accessId, $this->secretKey)
                ->timeout(15)
                ->post("{$this->endpoint}/url_metrics", [
                    'targets' => ['moz.com'],
                ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Connected successfully'];
            }

            return ['success' => false, 'message' => 'Connection failed: '.($response->json('error_message') ?? $response->status())];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Error: '.$e->getMessage()];
        }
    }
}
