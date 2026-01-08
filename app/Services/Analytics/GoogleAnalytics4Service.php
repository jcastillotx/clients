<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsAccount;
use App\Models\MarketingMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleAnalytics4Service
{
    private const API_BASE_URL = 'https://analyticsdata.googleapis.com/v1beta';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public function __construct(
        private AnalyticsAccount $account
    ) {}

    public function fetchMetrics(Carbon $startDate, Carbon $endDate): array
    {
        if ($this->account->needsTokenRefresh()) {
            $this->refreshAccessToken();
        }

        if (!$this->account->is_connected) {
            throw new \Exception('Analytics account not connected');
        }

        $dimensions = ['date'];
        $metrics = [
            'activeUsers',
            'sessions',
            'screenPageViews',
            'bounceRate',
            'averageSessionDuration',
            'conversions',
            'totalRevenue',
        ];

        $response = Http::withToken($this->account->access_token)
            ->post("{$this::API_BASE_URL}/properties/{$this->account->property_id}:runReport", [
                'dateRanges' => [
                    [
                        'startDate' => $startDate->format('Y-m-d'),
                        'endDate' => $endDate->format('Y-m-d'),
                    ],
                ],
                'dimensions' => array_map(fn($d) => ['name' => $d], $dimensions),
                'metrics' => array_map(fn($m) => ['name' => $m], $metrics),
                'keepEmptyRows' => true,
            ]);

        if ($response->failed()) {
            Log::error('GA4 API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to fetch GA4 metrics: ' . $response->body());
        }

        $data = $response->json();

        $this->account->update(['last_sync_at' => now()]);

        return $this->processAndStoreMetrics($data);
    }

    protected function processAndStoreMetrics(array $data): array
    {
        $stored = [];

        if (!isset($data['rows'])) {
            return $stored;
        }

        foreach ($data['rows'] as $row) {
            $date = Carbon::parse($row['dimensionValues'][0]['value']);
            $metricValues = $row['metricValues'];

            $metricsData = [
                'active_users' => (float) ($metricValues[0]['value'] ?? 0),
                'sessions' => (float) ($metricValues[1]['value'] ?? 0),
                'pageviews' => (float) ($metricValues[2]['value'] ?? 0),
                'bounce_rate' => (float) ($metricValues[3]['value'] ?? 0),
                'avg_session_duration' => (float) ($metricValues[4]['value'] ?? 0),
                'conversions' => (float) ($metricValues[5]['value'] ?? 0),
                'revenue' => (float) ($metricValues[6]['value'] ?? 0),
            ];

            foreach ($metricsData as $metricName => $value) {
                MarketingMetric::updateOrCreate(
                    [
                        'client_id' => $this->account->client_id,
                        'metric_date' => $date,
                        'source' => 'google_analytics_4',
                        'metric_name' => $metricName,
                    ],
                    [
                        'metric_type' => 'analytics',
                        'metric_value' => $value,
                        'meta' => [
                            'property_id' => $this->account->property_id,
                            'property_name' => $this->account->property_name,
                        ],
                    ]
                );

                $stored[] = [
                    'date' => $date->format('Y-m-d'),
                    'metric' => $metricName,
                    'value' => $value,
                ];
            }
        }

        return $stored;
    }

    public function fetchTrafficSources(Carbon $startDate, Carbon $endDate): array
    {
        if ($this->account->needsTokenRefresh()) {
            $this->refreshAccessToken();
        }

        $response = Http::withToken($this->account->access_token)
            ->post("{$this::API_BASE_URL}/properties/{$this->account->property_id}:runReport", [
                'dateRanges' => [
                    [
                        'startDate' => $startDate->format('Y-m-d'),
                        'endDate' => $endDate->format('Y-m-d'),
                    ],
                ],
                'dimensions' => [
                    ['name' => 'sessionSource'],
                    ['name' => 'sessionMedium'],
                ],
                'metrics' => [
                    ['name' => 'sessions'],
                    ['name' => 'activeUsers'],
                    ['name' => 'conversions'],
                ],
                'orderBys' => [
                    [
                        'metric' => ['metricName' => 'sessions'],
                        'desc' => true,
                    ],
                ],
                'limit' => 50,
            ]);

        if ($response->failed()) {
            throw new \Exception('Failed to fetch traffic sources: ' . $response->body());
        }

        return $response->json();
    }

    public function fetchTopPages(Carbon $startDate, Carbon $endDate, int $limit = 20): array
    {
        if ($this->account->needsTokenRefresh()) {
            $this->refreshAccessToken();
        }

        $response = Http::withToken($this->account->access_token)
            ->post("{$this::API_BASE_URL}/properties/{$this->account->property_id}:runReport", [
                'dateRanges' => [
                    [
                        'startDate' => $startDate->format('Y-m-d'),
                        'endDate' => $endDate->format('Y-m-d'),
                    ],
                ],
                'dimensions' => [
                    ['name' => 'pageTitle'],
                    ['name' => 'pagePath'],
                ],
                'metrics' => [
                    ['name' => 'screenPageViews'],
                    ['name' => 'activeUsers'],
                    ['name' => 'averageSessionDuration'],
                ],
                'orderBys' => [
                    [
                        'metric' => ['metricName' => 'screenPageViews'],
                        'desc' => true,
                    ],
                ],
                'limit' => $limit,
            ]);

        if ($response->failed()) {
            throw new \Exception('Failed to fetch top pages: ' . $response->body());
        }

        return $response->json();
    }

    public function fetchConversions(Carbon $startDate, Carbon $endDate): array
    {
        if ($this->account->needsTokenRefresh()) {
            $this->refreshAccessToken();
        }

        $response = Http::withToken($this->account->access_token)
            ->post("{$this::API_BASE_URL}/properties/{$this->account->property_id}:runReport", [
                'dateRanges' => [
                    [
                        'startDate' => $startDate->format('Y-m-d'),
                        'endDate' => $endDate->format('Y-m-d'),
                    ],
                ],
                'dimensions' => [
                    ['name' => 'eventName'],
                ],
                'metrics' => [
                    ['name' => 'eventCount'],
                    ['name' => 'conversions'],
                ],
                'dimensionFilter' => [
                    'filter' => [
                        'fieldName' => 'eventName',
                        'stringFilter' => [
                            'matchType' => 'CONTAINS',
                            'value' => 'conversion',
                        ],
                    ],
                ],
                'orderBys' => [
                    [
                        'metric' => ['metricName' => 'conversions'],
                        'desc' => true,
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new \Exception('Failed to fetch conversions: ' . $response->body());
        }

        return $response->json();
    }

    public function fetchRealtimeUsers(): array
    {
        if ($this->account->needsTokenRefresh()) {
            $this->refreshAccessToken();
        }

        $response = Http::withToken($this->account->access_token)
            ->post("{$this::API_BASE_URL}/properties/{$this->account->property_id}:runRealtimeReport", [
                'metrics' => [
                    ['name' => 'activeUsers'],
                ],
            ]);

        if ($response->failed()) {
            throw new \Exception('Failed to fetch realtime users: ' . $response->body());
        }

        return $response->json();
    }

    protected function refreshAccessToken(): void
    {
        if (!$this->account->refresh_token) {
            throw new \Exception('No refresh token available');
        }

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $this->account->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            Log::error('GA4 Token Refresh Failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to refresh access token');
        }

        $data = $response->json();

        $this->account->updateTokens(
            $data['access_token'],
            $data['refresh_token'] ?? null,
            now()->addSeconds($data['expires_in'] ?? 3600)
        );
    }

    public function testConnection(): bool
    {
        try {
            if ($this->account->needsTokenRefresh()) {
                $this->refreshAccessToken();
            }

            $response = Http::withToken($this->account->access_token)
                ->get("https://analyticsadmin.googleapis.com/v1beta/properties/{$this->account->property_id}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('GA4 Connection Test Failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
