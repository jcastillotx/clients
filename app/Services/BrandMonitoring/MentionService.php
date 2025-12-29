<?php

namespace App\Services\BrandMonitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mention API Service
 *
 * Commercial social listening & monitoring platform (from $29/month).
 * Features: Real-time mentions, sentiment analysis, influencer identification
 *
 * @see https://dev.mention.com/
 */
class MentionService
{
    protected string $endpoint = 'https://api.mention.com/api';

    protected ?string $apiKey;

    protected ?string $accountId;

    protected bool $enabled;

    public function __construct()
    {
        $this->apiKey = config('brand-monitoring.commercial.mention.api_key') ?: app('settings')->get('api.brand.mention.api_key');
        $this->accountId = config('brand-monitoring.commercial.mention.account_id') ?: app('settings')->get('api.brand.mention.account_id');
        $this->enabled = (bool) (config('brand-monitoring.commercial.mention.enabled') ?? app('settings')->get('api.brand.mention.enabled', false));
    }

    public function isConfigured(): bool
    {
        return $this->enabled && ! empty($this->apiKey);
    }

    /**
     * Get all alerts (monitoring projects)
     *
     * @return array<string, mixed>
     */
    public function getAlerts(): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Mention not configured', 'data' => []];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])
                ->timeout(30)
                ->get("{$this->endpoint}/accounts/{$this->accountId}/alerts");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'alerts' => $response->json('alerts') ?? [],
                ];
            }

            return ['error' => 'API request failed: '.$response->status(), 'data' => []];
        } catch (\Throwable $e) {
            Log::error('Mention get alerts error', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Get mentions for an alert
     *
     * @return array<string, mixed>
     */
    public function getMentions(string $alertId, ?string $since = null, int $limit = 100): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Mention not configured', 'data' => []];
        }

        $cacheKey = 'mention:mentions:'.md5($alertId.$since.$limit);

        return Cache::remember($cacheKey, 3600, function () use ($alertId, $since, $limit) {
            try {
                $params = ['limit' => $limit];
                if ($since) {
                    $params['since_id'] = $since;
                }

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$this->apiKey,
                ])
                    ->timeout(60)
                    ->get("{$this->endpoint}/accounts/{$this->accountId}/alerts/{$alertId}/mentions", $params);

                if ($response->successful()) {
                    $mentions = $response->json('mentions') ?? [];

                    return [
                        'success' => true,
                        'alert_id' => $alertId,
                        'mentions' => collect($mentions)->map(function ($mention) {
                            return [
                                'id' => $mention['id'] ?? null,
                                'title' => $mention['title'] ?? null,
                                'description' => $mention['description'] ?? null,
                                'original_url' => $mention['original_url'] ?? null,
                                'source_name' => $mention['source']['name'] ?? null,
                                'source_type' => $mention['source']['type'] ?? null,
                                'sentiment' => $mention['sentiment'] ?? null,
                                'reach' => $mention['reach'] ?? null,
                                'author' => $mention['author']['name'] ?? null,
                                'published_at' => $mention['published_at'] ?? null,
                            ];
                        })->toArray(),
                        'total' => count($mentions),
                    ];
                }

                return ['error' => 'API request failed: '.$response->status(), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('Mention get mentions error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get alert statistics
     *
     * @return array<string, mixed>
     */
    public function getAlertStats(string $alertId, string $period = 'day'): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Mention not configured', 'data' => []];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])
                ->timeout(30)
                ->get("{$this->endpoint}/accounts/{$this->accountId}/alerts/{$alertId}/statistics", [
                    'period' => $period,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'alert_id' => $alertId,
                    'statistics' => $response->json('statistics') ?? [],
                ];
            }

            return ['error' => 'API request failed: '.$response->status(), 'data' => []];
        } catch (\Throwable $e) {
            Log::error('Mention get stats error', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Get influencers for an alert
     *
     * @return array<string, mixed>
     */
    public function getInfluencers(string $alertId, int $limit = 50): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Mention not configured', 'data' => []];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])
                ->timeout(30)
                ->get("{$this->endpoint}/accounts/{$this->accountId}/alerts/{$alertId}/authors/top", [
                    'limit' => $limit,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'alert_id' => $alertId,
                    'influencers' => $response->json('authors') ?? [],
                ];
            }

            return ['error' => 'API request failed: '.$response->status(), 'data' => []];
        } catch (\Throwable $e) {
            Log::error('Mention get influencers error', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Create a new alert
     *
     * @param  array<string>  $keywords
     * @param  array<string>  $blockedKeywords
     * @param  array<string>  $sources
     * @return array<string, mixed>
     */
    public function createAlert(string $name, array $keywords, array $blockedKeywords = [], array $sources = []): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Mention not configured', 'data' => []];
        }

        try {
            $payload = [
                'name' => $name,
                'query' => [
                    'included_keywords' => $keywords,
                    'blocked_keywords' => $blockedKeywords,
                ],
            ];

            if (! empty($sources)) {
                $payload['sources'] = $sources;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post("{$this->endpoint}/accounts/{$this->accountId}/alerts", $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'alert' => $response->json('alert') ?? [],
                ];
            }

            return ['error' => 'API request failed: '.$response->status(), 'data' => []];
        } catch (\Throwable $e) {
            Log::error('Mention create alert error', ['error' => $e->getMessage()]);

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
            return ['success' => false, 'message' => 'Mention not configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])
                ->timeout(10)
                ->get("{$this->endpoint}/accounts/me");

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Connected successfully'];
            }

            return ['success' => false, 'message' => 'Connection failed: '.($response->json('message') ?? $response->status())];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Error: '.$e->getMessage()];
        }
    }
}
