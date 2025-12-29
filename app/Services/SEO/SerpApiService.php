<?php

namespace App\Services\SEO;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SerpApi Service
 *
 * Low-cost SERP scraping API (from $50/month for 5,000 searches).
 * Features: Google SERP results, rank tracking, local results
 *
 * @see https://serpapi.com/
 */
class SerpApiService
{
    protected string $endpoint = 'https://serpapi.com/search';

    protected ?string $apiKey;

    protected bool $enabled;

    public function __construct()
    {
        $this->apiKey = config('seo.low_cost.serpapi.api_key') ?: app('settings')->get('api.seo.serpapi.api_key');
        $this->enabled = (bool) (config('seo.low_cost.serpapi.enabled') ?? app('settings')->get('api.seo.serpapi.enabled', false));
    }

    public function isConfigured(): bool
    {
        return $this->enabled && ! empty($this->apiKey);
    }

    /**
     * Search Google and get SERP results
     *
     * @return array<string, mixed>
     */
    public function searchGoogle(
        string $query,
        string $location = 'United States',
        string $language = 'en',
        string $device = 'desktop',
        int $num = 100
    ): array {
        if (! $this->isConfigured()) {
            return ['error' => 'SerpApi not configured', 'data' => []];
        }

        $cacheKey = 'serpapi:google:'.md5($query.$location.$language.$device.$num);

        return Cache::remember($cacheKey, config('seo.cache.serp_data_ttl', 3600), function () use ($query, $location, $language, $device, $num) {
            try {
                $response = Http::timeout(60)
                    ->get($this->endpoint, [
                        'engine' => 'google',
                        'q' => $query,
                        'location' => $location,
                        'hl' => $language,
                        'gl' => 'us',
                        'device' => $device,
                        'num' => $num,
                        'api_key' => $this->apiKey,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'success' => true,
                        'query' => $query,
                        'location' => $location,
                        'organic_results' => collect($data['organic_results'] ?? [])->map(function ($item, $index) {
                            return [
                                'position' => $index + 1,
                                'title' => $item['title'] ?? null,
                                'link' => $item['link'] ?? null,
                                'displayed_link' => $item['displayed_link'] ?? null,
                                'snippet' => $item['snippet'] ?? null,
                                'snippet_highlighted' => $item['snippet_highlighted_words'] ?? [],
                                'sitelinks' => $item['sitelinks'] ?? null,
                                'rich_snippet' => $item['rich_snippet'] ?? null,
                            ];
                        })->toArray(),
                        'total_results' => $data['search_information']['total_results'] ?? null,
                        'related_searches' => $data['related_searches'] ?? [],
                        'knowledge_graph' => $data['knowledge_graph'] ?? null,
                        'local_results' => $data['local_results'] ?? null,
                        'ads' => $data['ads'] ?? [],
                        'search_metadata' => [
                            'id' => $data['search_metadata']['id'] ?? null,
                            'created_at' => $data['search_metadata']['created_at'] ?? null,
                        ],
                    ];
                }

                return ['error' => 'API request failed: '.($response->json('error') ?? $response->status()), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('SerpApi Google search error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Track ranking position for a domain
     *
     * @return array<string, mixed>
     */
    public function trackRanking(string $keyword, string $domain, string $location = 'United States'): array
    {
        $results = $this->searchGoogle($keyword, $location);

        if (isset($results['error'])) {
            return $results;
        }

        $domain = strtolower(parse_url($domain, PHP_URL_HOST) ?? $domain);
        $domain = preg_replace('/^www\./', '', $domain);

        $position = null;
        $rankingUrl = null;

        foreach ($results['organic_results'] ?? [] as $result) {
            $resultDomain = strtolower(parse_url($result['link'] ?? '', PHP_URL_HOST) ?? '');
            $resultDomain = preg_replace('/^www\./', '', $resultDomain);

            if (str_contains($resultDomain, $domain) || str_contains($domain, $resultDomain)) {
                $position = $result['position'];
                $rankingUrl = $result['link'];
                break;
            }
        }

        return [
            'success' => true,
            'keyword' => $keyword,
            'domain' => $domain,
            'location' => $location,
            'position' => $position,
            'ranking_url' => $rankingUrl,
            'found' => $position !== null,
            'total_results' => $results['total_results'] ?? null,
        ];
    }

    /**
     * Get local search results (Google Maps)
     *
     * @return array<string, mixed>
     */
    public function searchLocalResults(string $query, string $location): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'SerpApi not configured', 'data' => []];
        }

        $cacheKey = 'serpapi:local:'.md5($query.$location);

        return Cache::remember($cacheKey, config('seo.cache.serp_data_ttl', 3600), function () use ($query, $location) {
            try {
                $response = Http::timeout(60)
                    ->get($this->endpoint, [
                        'engine' => 'google_local',
                        'q' => $query,
                        'location' => $location,
                        'api_key' => $this->apiKey,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'success' => true,
                        'query' => $query,
                        'location' => $location,
                        'local_results' => collect($data['local_results'] ?? [])->map(function ($item, $index) {
                            return [
                                'position' => $index + 1,
                                'title' => $item['title'] ?? null,
                                'place_id' => $item['place_id'] ?? null,
                                'address' => $item['address'] ?? null,
                                'phone' => $item['phone'] ?? null,
                                'rating' => $item['rating'] ?? null,
                                'reviews' => $item['reviews'] ?? null,
                                'type' => $item['type'] ?? null,
                                'website' => $item['website'] ?? null,
                                'hours' => $item['hours'] ?? null,
                                'thumbnail' => $item['thumbnail'] ?? null,
                            ];
                        })->toArray(),
                    ];
                }

                return ['error' => 'API request failed: '.($response->json('error') ?? $response->status()), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('SerpApi local search error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get account information and remaining credits
     *
     * @return array<string, mixed>
     */
    public function getAccountInfo(): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'SerpApi not configured', 'data' => []];
        }

        try {
            $response = Http::timeout(10)
                ->get('https://serpapi.com/account', [
                    'api_key' => $this->apiKey,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'account_email' => $data['account_email'] ?? null,
                    'plan_name' => $data['plan_name'] ?? null,
                    'plan_searches_left' => $data['plan_searches_left'] ?? null,
                    'total_searches_left' => $data['total_searches_left'] ?? null,
                    'extra_credits' => $data['extra_credits'] ?? null,
                ];
            }

            return ['error' => 'API request failed: '.$response->status(), 'data' => []];
        } catch (\Throwable $e) {
            Log::error('SerpApi account info error', ['error' => $e->getMessage()]);

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
            return ['success' => false, 'message' => 'SerpApi not configured'];
        }

        try {
            $response = Http::timeout(10)
                ->get('https://serpapi.com/account', [
                    'api_key' => $this->apiKey,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'message' => 'Connected successfully. Searches left: '.($data['total_searches_left'] ?? 'N/A'),
                ];
            }

            return ['success' => false, 'message' => 'Connection failed: '.($response->json('error') ?? $response->status())];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Error: '.$e->getMessage()];
        }
    }
}
