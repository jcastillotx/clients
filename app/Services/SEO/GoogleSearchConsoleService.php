<?php

namespace App\Services\SEO;

use App\Models\Client;
use App\Models\KeywordRanking;
use App\Models\SeoKeyword;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Search Console API Service
 *
 * FREE API - provides real ranking data for verified sites.
 * Features: Search queries, impressions, clicks, positions, CTR
 *
 * @see https://developers.google.com/webmaster-tools/search-console-api-original
 */
class GoogleSearchConsoleService
{
    protected string $endpoint = 'https://www.googleapis.com/webmasters/v3';

    protected string $searchAnalyticsEndpoint = 'https://searchconsole.googleapis.com/v1';

    /**
     * Check if GSC is configured for a client
     */
    public function isConfigured(?Client $client = null): bool
    {
        if ($client && $client->gsc_refresh_token) {
            return true;
        }

        // Fall back to global config
        return !empty(config('seo.free.google_search_console.refresh_token'));
    }

    /**
     * Get access token (refresh if needed)
     */
    public function getAccessToken(Client $client): ?string
    {
        $cacheKey = "gsc_access_token:{$client->id}";

        return Cache::remember($cacheKey, 3500, function () use ($client) {
            $refreshToken = $client->gsc_refresh_token ?? config('seo.free.google_search_console.refresh_token');

            if (empty($refreshToken)) {
                return null;
            }

            try {
                $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                    'client_id' => config('services.google.client_id'),
                    'client_secret' => config('services.google.client_secret'),
                    'refresh_token' => $refreshToken,
                    'grant_type' => 'refresh_token',
                ]);

                if ($response->successful()) {
                    return $response->json('access_token');
                }

                Log::error('GSC token refresh failed', ['response' => $response->body()]);
                return null;
            } catch (\Throwable $e) {
                Log::error('GSC token refresh error', ['error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Get list of sites (properties) from Search Console
     */
    public function getSites(Client $client): array
    {
        $accessToken = $this->getAccessToken($client);
        if (!$accessToken) {
            return ['error' => 'Not authenticated', 'sites' => []];
        }

        try {
            $response = Http::withToken($accessToken)
                ->get("{$this->endpoint}/sites");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'sites' => $response->json('siteEntry', []),
                ];
            }

            return ['error' => 'Failed to fetch sites', 'sites' => []];
        } catch (\Throwable $e) {
            Log::error('GSC getSites error', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage(), 'sites' => []];
        }
    }

    /**
     * Get search analytics data (rankings, clicks, impressions)
     */
    public function getSearchAnalytics(
        Client $client,
        string $siteUrl,
        string $startDate,
        string $endDate,
        array $dimensions = ['query'],
        int $rowLimit = 100
    ): array {
        $accessToken = $this->getAccessToken($client);
        if (!$accessToken) {
            return ['error' => 'Not authenticated', 'data' => []];
        }

        $cacheKey = "gsc_analytics:{$client->id}:" . md5($siteUrl . $startDate . $endDate . implode(',', $dimensions));

        return Cache::remember($cacheKey, config('seo.cache.serp_data_ttl', 3600), function () use ($accessToken, $siteUrl, $startDate, $endDate, $dimensions, $rowLimit) {
            try {
                $encodedSiteUrl = urlencode($siteUrl);

                $response = Http::withToken($accessToken)
                    ->post("{$this->searchAnalyticsEndpoint}/sites/{$encodedSiteUrl}/searchAnalytics/query", [
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'dimensions' => $dimensions,
                        'rowLimit' => $rowLimit,
                        'startRow' => 0,
                    ]);

                if ($response->successful()) {
                    $rows = $response->json('rows', []);

                    return [
                        'success' => true,
                        'data' => array_map(function ($row) use ($dimensions) {
                            $result = [
                                'clicks' => $row['clicks'] ?? 0,
                                'impressions' => $row['impressions'] ?? 0,
                                'ctr' => round(($row['ctr'] ?? 0) * 100, 2),
                                'position' => round($row['position'] ?? 0, 1),
                            ];

                            // Map dimension values
                            foreach ($dimensions as $index => $dimension) {
                                $result[$dimension] = $row['keys'][$index] ?? null;
                            }

                            return $result;
                        }, $rows),
                        'row_count' => count($rows),
                    ];
                }

                return ['error' => 'API request failed: ' . $response->status(), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('GSC search analytics error', ['error' => $e->getMessage()]);
                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get top performing queries (keywords)
     */
    public function getTopQueries(Client $client, string $siteUrl, int $days = 28, int $limit = 50): array
    {
        $startDate = now()->subDays($days)->format('Y-m-d');
        $endDate = now()->subDay()->format('Y-m-d');

        return $this->getSearchAnalytics($client, $siteUrl, $startDate, $endDate, ['query'], $limit);
    }

    /**
     * Get top pages
     */
    public function getTopPages(Client $client, string $siteUrl, int $days = 28, int $limit = 50): array
    {
        $startDate = now()->subDays($days)->format('Y-m-d');
        $endDate = now()->subDay()->format('Y-m-d');

        return $this->getSearchAnalytics($client, $siteUrl, $startDate, $endDate, ['page'], $limit);
    }

    /**
     * Get performance by country
     */
    public function getPerformanceByCountry(Client $client, string $siteUrl, int $days = 28): array
    {
        $startDate = now()->subDays($days)->format('Y-m-d');
        $endDate = now()->subDay()->format('Y-m-d');

        return $this->getSearchAnalytics($client, $siteUrl, $startDate, $endDate, ['country'], 50);
    }

    /**
     * Get performance by device
     */
    public function getPerformanceByDevice(Client $client, string $siteUrl, int $days = 28): array
    {
        $startDate = now()->subDays($days)->format('Y-m-d');
        $endDate = now()->subDay()->format('Y-m-d');

        return $this->getSearchAnalytics($client, $siteUrl, $startDate, $endDate, ['device'], 10);
    }

    /**
     * Get daily performance trend
     */
    public function getDailyTrend(Client $client, string $siteUrl, int $days = 28): array
    {
        $startDate = now()->subDays($days)->format('Y-m-d');
        $endDate = now()->subDay()->format('Y-m-d');

        return $this->getSearchAnalytics($client, $siteUrl, $startDate, $endDate, ['date'], 100);
    }

    /**
     * Sync GSC data to local database for a client
     */
    public function syncKeywordData(Client $client, string $siteUrl): array
    {
        $result = $this->getTopQueries($client, $siteUrl, 28, 500);

        if (!($result['success'] ?? false)) {
            return ['success' => false, 'message' => $result['error'] ?? 'Failed to fetch data'];
        }

        $synced = 0;
        $updated = 0;

        foreach ($result['data'] as $row) {
            $keyword = SeoKeyword::updateOrCreate(
                [
                    'client_id' => $client->id,
                    'keyword' => $row['query'],
                    'website_url' => $siteUrl,
                ],
                [
                    'current_position' => (int) round($row['position']),
                    'search_volume' => $row['impressions'], // Use impressions as proxy for volume
                    'tracking_enabled' => true,
                    'meta' => [
                        'clicks' => $row['clicks'],
                        'impressions' => $row['impressions'],
                        'ctr' => $row['ctr'],
                        'last_synced' => now()->toDateTimeString(),
                        'source' => 'google_search_console',
                    ],
                ]
            );

            if ($keyword->wasRecentlyCreated) {
                $synced++;
            } else {
                $updated++;
            }

            // Also save to keyword rankings history
            KeywordRanking::create([
                'seo_keyword_id' => $keyword->id,
                'position' => (int) round($row['position']),
                'search_engine' => 'google',
                'device' => 'all',
                'tracked_at' => now(),
                'meta' => [
                    'clicks' => $row['clicks'],
                    'impressions' => $row['impressions'],
                    'ctr' => $row['ctr'],
                ],
            ]);
        }

        return [
            'success' => true,
            'synced' => $synced,
            'updated' => $updated,
            'total' => count($result['data']),
        ];
    }

    /**
     * Test API connection
     */
    public function testConnection(Client $client): array
    {
        $accessToken = $this->getAccessToken($client);

        if (!$accessToken) {
            return ['success' => false, 'message' => 'Failed to obtain access token'];
        }

        try {
            $response = Http::withToken($accessToken)
                ->get("{$this->endpoint}/sites");

            if ($response->successful()) {
                $sites = $response->json('siteEntry', []);
                return [
                    'success' => true,
                    'message' => 'Connected successfully',
                    'sites_count' => count($sites),
                ];
            }

            return ['success' => false, 'message' => 'API request failed: ' . $response->status()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
