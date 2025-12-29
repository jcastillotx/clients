<?php

namespace App\Services\BrandMonitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Brandwatch API Service
 *
 * Enterprise brand intelligence platform (custom pricing, typically $800-2000+/month).
 * Features: Social listening, sentiment, influencer tracking, crisis detection
 *
 * @see https://developers.brandwatch.com/
 */
class BrandwatchService
{
    protected string $endpoint = 'https://api.brandwatch.com';

    protected ?string $apiKey;

    protected ?string $apiSecret;

    protected ?string $projectId;

    protected bool $enabled;

    protected ?string $accessToken = null;

    public function __construct()
    {
        $this->apiKey = config('brand-monitoring.commercial.brandwatch.api_key') ?: app('settings')->get('api.brand.brandwatch.api_key');
        $this->apiSecret = config('brand-monitoring.commercial.brandwatch.api_secret') ?: app('settings')->get('api.brand.brandwatch.api_secret');
        $this->projectId = config('brand-monitoring.commercial.brandwatch.project_id') ?: app('settings')->get('api.brand.brandwatch.project_id');
        $this->enabled = (bool) (config('brand-monitoring.commercial.brandwatch.enabled') ?? app('settings')->get('api.brand.brandwatch.enabled', false));
    }

    public function isConfigured(): bool
    {
        return $this->enabled && ! empty($this->apiKey) && ! empty($this->apiSecret);
    }

    /**
     * Get access token (OAuth2)
     */
    protected function getAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $cacheKey = 'brandwatch:access_token';
        $this->accessToken = Cache::remember($cacheKey, 3500, function () {
            try {
                $response = Http::asForm()->post("{$this->endpoint}/oauth/token", [
                    'grant_type' => 'api-password',
                    'client_id' => $this->apiKey,
                    'client_secret' => $this->apiSecret,
                ]);

                if ($response->successful()) {
                    return $response->json('access_token');
                }

                return null;
            } catch (\Throwable $e) {
                Log::error('Brandwatch token error', ['error' => $e->getMessage()]);

                return null;
            }
        });

        return $this->accessToken;
    }

    /**
     * Get list of projects
     *
     * @return array<string, mixed>
     */
    public function getProjects(): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Brandwatch not configured', 'data' => []];
        }

        $token = $this->getAccessToken();
        if (! $token) {
            return ['error' => 'Failed to get access token', 'data' => []];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->get("{$this->endpoint}/projects");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'projects' => $response->json('results') ?? [],
                ];
            }

            return ['error' => 'API request failed: '.$response->status(), 'data' => []];
        } catch (\Throwable $e) {
            Log::error('Brandwatch get projects error', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Get queries (monitoring topics) for a project
     *
     * @return array<string, mixed>
     */
    public function getQueries(?string $projectId = null): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Brandwatch not configured', 'data' => []];
        }

        $projectId = $projectId ?? $this->projectId;
        if (empty($projectId)) {
            return ['error' => 'Project ID not specified', 'data' => []];
        }

        $token = $this->getAccessToken();
        if (! $token) {
            return ['error' => 'Failed to get access token', 'data' => []];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->get("{$this->endpoint}/projects/{$projectId}/queries");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'queries' => $response->json('results') ?? [],
                ];
            }

            return ['error' => 'API request failed: '.$response->status(), 'data' => []];
        } catch (\Throwable $e) {
            Log::error('Brandwatch get queries error', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Get mentions for a query
     *
     * @return array<string, mixed>
     */
    public function getMentions(string $queryId, ?string $projectId = null, ?string $startDate = null, ?string $endDate = null, int $pageSize = 100): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Brandwatch not configured', 'data' => []];
        }

        $projectId = $projectId ?? $this->projectId;
        if (empty($projectId)) {
            return ['error' => 'Project ID not specified', 'data' => []];
        }

        $token = $this->getAccessToken();
        if (! $token) {
            return ['error' => 'Failed to get access token', 'data' => []];
        }

        $cacheKey = 'brandwatch:mentions:'.md5($projectId.$queryId.$startDate.$endDate.$pageSize);

        return Cache::remember($cacheKey, 3600, function () use ($projectId, $queryId, $startDate, $endDate, $pageSize, $token) {
            try {
                $params = [
                    'queryId' => $queryId,
                    'pageSize' => $pageSize,
                ];

                if ($startDate) {
                    $params['startDate'] = $startDate;
                }
                if ($endDate) {
                    $params['endDate'] = $endDate;
                }

                $response = Http::withToken($token)
                    ->timeout(60)
                    ->get("{$this->endpoint}/projects/{$projectId}/data/mentions", $params);

                if ($response->successful()) {
                    $mentions = $response->json('results') ?? [];

                    return [
                        'success' => true,
                        'project_id' => $projectId,
                        'query_id' => $queryId,
                        'mentions' => collect($mentions)->map(function ($mention) {
                            return [
                                'id' => $mention['id'] ?? null,
                                'title' => $mention['title'] ?? null,
                                'content' => $mention['fullText'] ?? null,
                                'url' => $mention['url'] ?? null,
                                'domain' => $mention['domain'] ?? null,
                                'page_type' => $mention['pageType'] ?? null,
                                'sentiment' => $mention['sentiment'] ?? null,
                                'reach' => $mention['reachEstimate'] ?? null,
                                'engagement' => $mention['engagement'] ?? null,
                                'author' => $mention['author'] ?? null,
                                'location' => $mention['location'] ?? null,
                                'published_at' => $mention['date'] ?? null,
                            ];
                        })->toArray(),
                        'total' => $response->json('resultsTotal') ?? count($mentions),
                    ];
                }

                return ['error' => 'API request failed: '.$response->status(), 'data' => []];
            } catch (\Throwable $e) {
                Log::error('Brandwatch get mentions error', ['error' => $e->getMessage()]);

                return ['error' => $e->getMessage(), 'data' => []];
            }
        });
    }

    /**
     * Get volume/sentiment summary
     *
     * @return array<string, mixed>
     */
    public function getSummary(string $queryId, ?string $projectId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Brandwatch not configured', 'data' => []];
        }

        $projectId = $projectId ?? $this->projectId;
        if (empty($projectId)) {
            return ['error' => 'Project ID not specified', 'data' => []];
        }

        $token = $this->getAccessToken();
        if (! $token) {
            return ['error' => 'Failed to get access token', 'data' => []];
        }

        try {
            $params = ['queryId' => $queryId];
            if ($startDate) {
                $params['startDate'] = $startDate;
            }
            if ($endDate) {
                $params['endDate'] = $endDate;
            }

            $response = Http::withToken($token)
                ->timeout(30)
                ->get("{$this->endpoint}/projects/{$projectId}/data/volume/days/sentiment", $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'project_id' => $projectId,
                    'query_id' => $queryId,
                    'summary' => $response->json('results') ?? [],
                ];
            }

            return ['error' => 'API request failed: '.$response->status(), 'data' => []];
        } catch (\Throwable $e) {
            Log::error('Brandwatch get summary error', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Get top authors/influencers
     *
     * @return array<string, mixed>
     */
    public function getTopAuthors(string $queryId, ?string $projectId = null, int $limit = 50): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Brandwatch not configured', 'data' => []];
        }

        $projectId = $projectId ?? $this->projectId;
        if (empty($projectId)) {
            return ['error' => 'Project ID not specified', 'data' => []];
        }

        $token = $this->getAccessToken();
        if (! $token) {
            return ['error' => 'Failed to get access token', 'data' => []];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->get("{$this->endpoint}/projects/{$projectId}/data/volume/authors", [
                    'queryId' => $queryId,
                    'pageSize' => $limit,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'project_id' => $projectId,
                    'query_id' => $queryId,
                    'authors' => $response->json('results') ?? [],
                ];
            }

            return ['error' => 'API request failed: '.$response->status(), 'data' => []];
        } catch (\Throwable $e) {
            Log::error('Brandwatch get authors error', ['error' => $e->getMessage()]);

            return ['error' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Get topic cloud / trending topics
     *
     * @return array<string, mixed>
     */
    public function getTopics(string $queryId, ?string $projectId = null): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Brandwatch not configured', 'data' => []];
        }

        $projectId = $projectId ?? $this->projectId;
        if (empty($projectId)) {
            return ['error' => 'Project ID not specified', 'data' => []];
        }

        $token = $this->getAccessToken();
        if (! $token) {
            return ['error' => 'Failed to get access token', 'data' => []];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->get("{$this->endpoint}/projects/{$projectId}/data/topics", [
                    'queryId' => $queryId,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'project_id' => $projectId,
                    'query_id' => $queryId,
                    'topics' => $response->json('results') ?? [],
                ];
            }

            return ['error' => 'API request failed: '.$response->status(), 'data' => []];
        } catch (\Throwable $e) {
            Log::error('Brandwatch get topics error', ['error' => $e->getMessage()]);

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
            return ['success' => false, 'message' => 'Brandwatch not configured'];
        }

        $token = $this->getAccessToken();
        if (! $token) {
            return ['success' => false, 'message' => 'Failed to authenticate'];
        }

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->get("{$this->endpoint}/me");

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Connected successfully'];
            }

            return ['success' => false, 'message' => 'Connection failed: '.$response->status()];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Error: '.$e->getMessage()];
        }
    }
}
