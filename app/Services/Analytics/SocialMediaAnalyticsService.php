<?php

namespace App\Services\Analytics;

use App\Models\ContentCalendarItem;
use App\Models\MarketingMetric;
use App\Models\SocialAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialMediaAnalyticsService
{
    public function __construct(
        private SocialAccount $account
    ) {}

    public function fetchMetrics(Carbon $startDate, Carbon $endDate): array
    {
        return match ($this->account->platform) {
            'facebook' => $this->fetchFacebookMetrics($startDate, $endDate),
            'instagram' => $this->fetchInstagramMetrics($startDate, $endDate),
            'linkedin' => $this->fetchLinkedInMetrics($startDate, $endDate),
            'twitter' => $this->fetchTwitterMetrics($startDate, $endDate),
            default => [],
        };
    }

    protected function fetchFacebookMetrics(Carbon $startDate, Carbon $endDate): array
    {
        if ($this->account->needsTokenRefresh()) {
            $this->refreshToken();
        }

        $metrics = [
            'page_impressions',
            'page_engaged_users',
            'page_post_engagements',
            'page_fans',
            'page_views_total',
        ];

        $response = Http::get("https://graph.facebook.com/v18.0/{$this->account->account_id}/insights", [
            'access_token' => $this->account->access_token,
            'metric' => implode(',', $metrics),
            'since' => $startDate->timestamp,
            'until' => $endDate->timestamp,
            'period' => 'day',
        ]);

        if ($response->failed()) {
            Log::error('Facebook Analytics Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [];
        }

        $data = $response->json();
        return $this->storeFacebookMetrics($data);
    }

    protected function storeFacebookMetrics(array $data): array
    {
        $stored = [];

        foreach ($data['data'] ?? [] as $metric) {
            $metricName = $metric['name'];

            foreach ($metric['values'] ?? [] as $value) {
                $date = Carbon::parse($value['end_time']);

                MarketingMetric::updateOrCreate(
                    [
                        'client_id' => $this->account->client_id,
                        'metric_date' => $date,
                        'source' => 'facebook',
                        'metric_name' => $metricName,
                    ],
                    [
                        'metric_type' => 'social_media',
                        'metric_value' => $value['value'] ?? 0,
                        'meta' => [
                            'account_id' => $this->account->account_id,
                            'account_name' => $this->account->account_name,
                        ],
                    ]
                );

                $stored[] = [
                    'date' => $date->format('Y-m-d'),
                    'metric' => $metricName,
                    'value' => $value['value'] ?? 0,
                ];
            }
        }

        $this->account->update(['last_sync_at' => now()]);

        return $stored;
    }

    protected function fetchInstagramMetrics(Carbon $startDate, Carbon $endDate): array
    {
        if ($this->account->needsTokenRefresh()) {
            $this->refreshToken();
        }

        $metrics = [
            'impressions',
            'reach',
            'profile_views',
            'follower_count',
        ];

        $response = Http::get("https://graph.facebook.com/v18.0/{$this->account->account_id}/insights", [
            'access_token' => $this->account->access_token,
            'metric' => implode(',', $metrics),
            'since' => $startDate->timestamp,
            'until' => $endDate->timestamp,
            'period' => 'day',
        ]);

        if ($response->failed()) {
            Log::error('Instagram Analytics Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [];
        }

        $data = $response->json();
        return $this->storeInstagramMetrics($data);
    }

    protected function storeInstagramMetrics(array $data): array
    {
        $stored = [];

        foreach ($data['data'] ?? [] as $metric) {
            $metricName = $metric['name'];

            foreach ($metric['values'] ?? [] as $value) {
                $date = Carbon::parse($value['end_time']);

                MarketingMetric::updateOrCreate(
                    [
                        'client_id' => $this->account->client_id,
                        'metric_date' => $date,
                        'source' => 'instagram',
                        'metric_name' => $metricName,
                    ],
                    [
                        'metric_type' => 'social_media',
                        'metric_value' => $value['value'] ?? 0,
                        'meta' => [
                            'account_id' => $this->account->account_id,
                            'account_name' => $this->account->account_name,
                        ],
                    ]
                );

                $stored[] = [
                    'date' => $date->format('Y-m-d'),
                    'metric' => $metricName,
                    'value' => $value['value'] ?? 0,
                ];
            }
        }

        $this->account->update(['last_sync_at' => now()]);

        return $stored;
    }

    protected function fetchLinkedInMetrics(Carbon $startDate, Carbon $endDate): array
    {
        if ($this->account->needsTokenRefresh()) {
            $this->refreshToken();
        }

        $organizationUrn = $this->account->account_id;

        $response = Http::withToken($this->account->access_token)
            ->get("https://api.linkedin.com/v2/organizationalEntityShareStatistics", [
                'q' => 'organizationalEntity',
                'organizationalEntity' => $organizationUrn,
                'timeIntervals.timeGranularityType' => 'DAY',
                'timeIntervals.timeRange.start' => $startDate->timestamp * 1000,
                'timeIntervals.timeRange.end' => $endDate->timestamp * 1000,
            ]);

        if ($response->failed()) {
            Log::error('LinkedIn Analytics Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [];
        }

        $data = $response->json();
        return $this->storeLinkedInMetrics($data);
    }

    protected function storeLinkedInMetrics(array $data): array
    {
        $stored = [];

        foreach ($data['elements'] ?? [] as $element) {
            $timestamp = $element['timeRange']['start'] ?? null;
            if (!$timestamp) {
                continue;
            }

            $date = Carbon::createFromTimestampMs($timestamp);

            $metrics = [
                'impressions' => $element['totalShareStatistics']['impressionCount'] ?? 0,
                'clicks' => $element['totalShareStatistics']['clickCount'] ?? 0,
                'shares' => $element['totalShareStatistics']['shareCount'] ?? 0,
                'engagement' => $element['totalShareStatistics']['engagement'] ?? 0,
            ];

            foreach ($metrics as $metricName => $value) {
                MarketingMetric::updateOrCreate(
                    [
                        'client_id' => $this->account->client_id,
                        'metric_date' => $date,
                        'source' => 'linkedin',
                        'metric_name' => $metricName,
                    ],
                    [
                        'metric_type' => 'social_media',
                        'metric_value' => $value,
                        'meta' => [
                            'account_id' => $this->account->account_id,
                            'account_name' => $this->account->account_name,
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

        $this->account->update(['last_sync_at' => now()]);

        return $stored;
    }

    protected function fetchTwitterMetrics(Carbon $startDate, Carbon $endDate): array
    {
        if ($this->account->needsTokenRefresh()) {
            $this->refreshToken();
        }

        $response = Http::withToken($this->account->access_token)
            ->get("https://api.twitter.com/2/users/{$this->account->account_id}/metrics", [
                'start_time' => $startDate->toIso8601String(),
                'end_time' => $endDate->toIso8601String(),
                'granularity' => 'day',
                'metric.fields' => 'impressions,engagements,retweets,replies,likes,quotes',
            ]);

        if ($response->failed()) {
            Log::error('Twitter Analytics Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [];
        }

        $data = $response->json();
        return $this->storeTwitterMetrics($data);
    }

    protected function storeTwitterMetrics(array $data): array
    {
        $stored = [];

        foreach ($data['data'] ?? [] as $day) {
            $date = Carbon::parse($day['start']);

            $metrics = [
                'impressions' => $day['impressions'] ?? 0,
                'engagements' => $day['engagements'] ?? 0,
                'retweets' => $day['retweets'] ?? 0,
                'replies' => $day['replies'] ?? 0,
                'likes' => $day['likes'] ?? 0,
                'quotes' => $day['quotes'] ?? 0,
            ];

            foreach ($metrics as $metricName => $value) {
                MarketingMetric::updateOrCreate(
                    [
                        'client_id' => $this->account->client_id,
                        'metric_date' => $date,
                        'source' => 'twitter',
                        'metric_name' => $metricName,
                    ],
                    [
                        'metric_type' => 'social_media',
                        'metric_value' => $value,
                        'meta' => [
                            'account_id' => $this->account->account_id,
                            'account_name' => $this->account->account_name,
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

        $this->account->update(['last_sync_at' => now()]);

        return $stored;
    }

    public function getPostPerformance(ContentCalendarItem $post): array
    {
        if (!$post->post_id || !$post->published_at) {
            return [];
        }

        return match ($this->account->platform) {
            'facebook' => $this->getFacebookPostMetrics($post->post_id),
            'instagram' => $this->getInstagramPostMetrics($post->post_id),
            'linkedin' => $this->getLinkedInPostMetrics($post->post_id),
            'twitter' => $this->getTwitterPostMetrics($post->post_id),
            default => [],
        };
    }

    protected function getFacebookPostMetrics(string $postId): array
    {
        $response = Http::get("https://graph.facebook.com/v18.0/{$postId}/insights", [
            'access_token' => $this->account->access_token,
            'metric' => 'post_impressions,post_engaged_users,post_clicks,post_reactions_like_total',
        ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json();
    }

    protected function getInstagramPostMetrics(string $postId): array
    {
        $response = Http::get("https://graph.facebook.com/v18.0/{$postId}/insights", [
            'access_token' => $this->account->access_token,
            'metric' => 'impressions,reach,engagement,saved',
        ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json();
    }

    protected function getLinkedInPostMetrics(string $postId): array
    {
        $response = Http::withToken($this->account->access_token)
            ->get("https://api.linkedin.com/v2/socialActions/{$postId}");

        if ($response->failed()) {
            return [];
        }

        return $response->json();
    }

    protected function getTwitterPostMetrics(string $postId): array
    {
        $response = Http::withToken($this->account->access_token)
            ->get("https://api.twitter.com/2/tweets/{$postId}", [
                'tweet.fields' => 'public_metrics',
            ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json();
    }

    protected function refreshToken(): void
    {
        if (!$this->account->refresh_token) {
            throw new \Exception('No refresh token available');
        }

        $tokenUrl = match ($this->account->platform) {
            'facebook', 'instagram' => 'https://graph.facebook.com/v18.0/oauth/access_token',
            'linkedin' => 'https://www.linkedin.com/oauth/v2/accessToken',
            'twitter' => 'https://api.twitter.com/2/oauth2/token',
            default => throw new \Exception('Unsupported platform'),
        };

        $params = match ($this->account->platform) {
            'facebook', 'instagram' => [
                'grant_type' => 'fb_exchange_token',
                'client_id' => config("services.{$this->account->platform}.client_id"),
                'client_secret' => config("services.{$this->account->platform}.client_secret"),
                'fb_exchange_token' => $this->account->access_token,
            ],
            'linkedin', 'twitter' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->account->refresh_token,
                'client_id' => config("services.{$this->account->platform}.client_id"),
                'client_secret' => config("services.{$this->account->platform}.client_secret"),
            ],
            default => [],
        };

        $response = Http::asForm()->post($tokenUrl, $params);

        if ($response->failed()) {
            Log::error('Social Media Token Refresh Failed', [
                'platform' => $this->account->platform,
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
}
