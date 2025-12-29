<?php

namespace App\Services\BrandMonitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Brand24 API Service
 *
 * Commercial online reputation monitoring platform (from $49/month).
 * Features: Online mentions, sentiment analysis, influence score, reach metrics
 *
 * @see https://brand24.com/api/
 */
class Brand24Service
{
    protected string $endpoint = 'https://api.brand24.com';

    protected ?string $apiKey;

    protected ?string $projectId;

    protected bool $enabled;

    public function __construct()
    {
        $this->apiKey = config('brand-monitoring.commercial.brand24.api_key') ?: app('settings')->get('api.brand.brand24.api_key');
        $this->projectId = config('brand-monitoring.commercial.brand24.project_id') ?: app('settings')->get('api.brand.brand24.project_id');
        $this->enabled = (bool) (config('brand-monitoring.commercial.brand24.enabled') ?? app('settings')->get('api.brand.brand24.enabled', false));
    }

    public function isConfigured(): bool
    {
        return $this->enabled && ! empty($this->apiKey);
    }

    /**
     * Get list of projects
     *
     * @return array<string, mixed>
     */
    public function getProjects(): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Brand24 not configured', 'data' => []];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])
                ->timeout(30)
                ->get("{$this->endpoint}/v3/account/projects");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'projects' => $response->json('projects') ?? [],
                ];
            }

            return ['error' => 'API request failed: '.$response->status(), 'data' => []];
        } catch (\Throwable $e) {
            Log::error('Brand24 get projects error', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Get mentions for a project
     *
     * @return array<string, mixed>
     */
    public function getMentions(?string $projectId = null, ?string $since = null, int $limit = 100): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Brand24 not configured', 'data' => []];
        }

        $projectId = $projectId ?? $this->projectId;
        if (empty($projectId)) {
            return ['error' => 'Project ID not specified', 'data' => []];
        }

        $cacheKey = 'brand24:mentions:'.md5($projectId.$since.$limit);

        return Cache::remember($cacheKey, 3600, function () use ($projectId, $since, $limit) {
            try {
                $params = [
                    'limit' => $limit,
                    'projectId' => $projectId,
                ];
                if ($since) {
                    $params['sinceDate'] = $since;
                }

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$this->apiKey,
                ])
                    ->timeout(60)
                    ->get("{$this->endpoint}/v3/mentions/list", $params);

                if ($response->successful()) {
                    $mentions = $response->json('mentions') ?? [];

                    return [
                        'success' => true,
                        'project_id' => $projectId,
                        'mentions' => collect($mentions)->map(function ($mention) {
                            return [
                                'id' => $mention['id'] ?? null,
                                'title' => $mention['title'] ?? null,
                                'description' => $mention['description'] ?? null,
                                'url' => $mention['url'] ?? null,
                                'source_type' => $mention['sourceType'] ?? null,
                                'domain' => $mention['domain'] ?? null,
                                'sentiment' => $mention['sentiment'] ?? null,
                                'influence_score' => $mention['influenceScore'] ?? null,
                                'reach' => $mention['reach'] ?? null,
                                'author' => $mention['author'] ?? null,
                                'published_at' => $mention['date'] ?? null,
                            ];
                        })->toArray(),
                        'total' => count($mentions),
                    ];
                }

                return ['error' => 'API request failed: '.$response->status(), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('Brand24 get mentions error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get project summary/statistics
     *
     * @return array<string, mixed>
     */
    public function getProjectSummary(?string $projectId = null, string $period = 'week'): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Brand24 not configured', 'data' => []];
        }

        $projectId = $projectId ?? $this->projectId;
        if (empty($projectId)) {
            return ['error' => 'Project ID not specified', 'data' => []];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])
                ->timeout(30)
                ->get("{$this->endpoint}/v3/projects/{$projectId}/summary", [
                    'period' => $period,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'project_id' => $projectId,
                    'summary' => [
                        'total_mentions' => $data['totalMentions'] ?? 0,
                        'positive_mentions' => $data['positiveMentions'] ?? 0,
                        'negative_mentions' => $data['negativeMentions'] ?? 0,
                        'neutral_mentions' => $data['neutralMentions'] ?? 0,
                        'total_reach' => $data['totalReach'] ?? 0,
                        'social_media_reach' => $data['socialMediaReach'] ?? 0,
                        'non_social_reach' => $data['nonSocialReach'] ?? 0,
                        'avg_sentiment' => $data['averageSentiment'] ?? null,
                        'influencer_score' => $data['influencerScore'] ?? null,
                    ],
                ];
            }

            return ['error' => 'API request failed: '.$response->status(), 'data' => []];
        } catch (\Throwable $e) {
            Log::error('Brand24 get summary error', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Get top influencers
     *
     * @return array<string, mixed>
     */
    public function getInfluencers(?string $projectId = null, int $limit = 50): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Brand24 not configured', 'data' => []];
        }

        $projectId = $projectId ?? $this->projectId;
        if (empty($projectId)) {
            return ['error' => 'Project ID not specified', 'data' => []];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])
                ->timeout(30)
                ->get("{$this->endpoint}/v3/projects/{$projectId}/influencers", [
                    'limit' => $limit,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'project_id' => $projectId,
                    'influencers' => $response->json('influencers') ?? [],
                ];
            }

            return ['error' => 'API request failed: '.$response->status(), 'data' => []];
        } catch (\Throwable $e) {
            Log::error('Brand24 get influencers error', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Get sentiment breakdown by source
     *
     * @return array<string, mixed>
     */
    public function getSentimentBreakdown(?string $projectId = null): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Brand24 not configured', 'data' => []];
        }

        $projectId = $projectId ?? $this->projectId;
        if (empty($projectId)) {
            return ['error' => 'Project ID not specified', 'data' => []];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])
                ->timeout(30)
                ->get("{$this->endpoint}/v3/projects/{$projectId}/sentiment");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'project_id' => $projectId,
                    'sentiment' => $response->json() ?? [],
                ];
            }

            return ['error' => 'API request failed: '.$response->status(), 'data' => []];
        } catch (\Throwable $e) {
            Log::error('Brand24 get sentiment error', ['error' => $e->getMessage()]);

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
            return ['success' => false, 'message' => 'Brand24 not configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])
                ->timeout(10)
                ->get("{$this->endpoint}/v3/account/projects");

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Connected successfully'];
            }

            return ['success' => false, 'message' => 'Connection failed: '.($response->json('message') ?? $response->status())];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Error: '.$e->getMessage()];
        }
    }
}
