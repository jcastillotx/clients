<?php

namespace App\Services\Ads;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use App\Models\AdSet;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleAdsService
{
    private const API_VERSION = 'v16';
    private const API_BASE_URL = 'https://googleads.googleapis.com';

    public function __construct(
        private AdAccount $account
    ) {}

    public function createCampaign(AdCampaign $campaign): array
    {
        if ($this->account->needsTokenRefresh()) {
            $this->refreshAccessToken();
        }

        $customerId = $this->account->account_id;

        $campaignData = [
            'name' => $campaign->name,
            'status' => $this->mapStatus($campaign->status),
            'advertising_channel_type' => 'SEARCH',
            'campaign_budget' => [
                'amount_micros' => $campaign->daily_budget * 1000000,
            ],
        ];

        if ($campaign->start_date) {
            $campaignData['start_date'] = $campaign->start_date->format('Ymd');
        }

        if ($campaign->end_date) {
            $campaignData['end_date'] = $campaign->end_date->format('Ymd');
        }

        $response = Http::withToken($this->account->access_token)
            ->post(
                $this::API_BASE_URL . '/' . $this::API_VERSION . "/customers/{$customerId}/campaigns:mutate",
                ['operations' => [['create' => $campaignData]]]
            );

        if ($response->failed()) {
            Log::error('Google Ads campaign creation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to create Google Ads campaign: ' . $response->body());
        }

        return $response->json();
    }

    public function fetchCampaigns(): array
    {
        if ($this->account->needsTokenRefresh()) {
            $this->refreshAccessToken();
        }

        $customerId = $this->account->account_id;

        $query = "
            SELECT
                campaign.id,
                campaign.name,
                campaign.status,
                campaign.advertising_channel_type,
                metrics.impressions,
                metrics.clicks,
                metrics.conversions,
                metrics.cost_micros
            FROM campaign
            WHERE segments.date DURING LAST_30_DAYS
        ";

        $response = Http::withToken($this->account->access_token)
            ->post(
                $this::API_BASE_URL . '/' . $this::API_VERSION . "/customers/{$customerId}/googleAds:search",
                ['query' => $query]
            );

        if ($response->failed()) {
            Log::error('Google Ads campaign fetch failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [];
        }

        return $response->json()['results'] ?? [];
    }

    public function fetchMetrics(AdCampaign $campaign, Carbon $startDate, Carbon $endDate): array
    {
        if ($this->account->needsTokenRefresh()) {
            $this->refreshAccessToken();
        }

        $customerId = $this->account->account_id;
        $campaignId = $campaign->platform_campaign_id;

        $query = "
            SELECT
                segments.date,
                metrics.impressions,
                metrics.clicks,
                metrics.conversions,
                metrics.cost_micros,
                metrics.ctr,
                metrics.average_cpc
            FROM campaign
            WHERE campaign.id = {$campaignId}
                AND segments.date BETWEEN '{$startDate->format('Y-m-d')}' AND '{$endDate->format('Y-m-d')}'
        ";

        $response = Http::withToken($this->account->access_token)
            ->post(
                $this::API_BASE_URL . '/' . $this::API_VERSION . "/customers/{$customerId}/googleAds:search",
                ['query' => $query]
            );

        if ($response->failed()) {
            Log::error('Google Ads metrics fetch failed', [
                'campaign_id' => $campaignId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [];
        }

        $results = $response->json()['results'] ?? [];

        return $this->storeMetrics($campaign, $results);
    }

    protected function storeMetrics(AdCampaign $campaign, array $results): array
    {
        $stored = [];

        foreach ($results as $row) {
            $date = Carbon::parse($row['segments']['date']);
            $metrics = $row['metrics'];

            AdMetric::updateOrCreate(
                [
                    'date' => $date,
                    'entity_type' => 'campaign',
                    'entity_id' => $campaign->id,
                ],
                [
                    'client_id' => $campaign->client_id,
                    'impressions' => $metrics['impressions'] ?? 0,
                    'clicks' => $metrics['clicks'] ?? 0,
                    'conversions' => $metrics['conversions'] ?? 0,
                    'spend' => ($metrics['cost_micros'] ?? 0) / 1000000,
                    'platform_metrics' => $metrics,
                ]
            );

            $stored[] = [
                'date' => $date->format('Y-m-d'),
                'impressions' => $metrics['impressions'] ?? 0,
                'clicks' => $metrics['clicks'] ?? 0,
            ];
        }

        $this->account->update(['last_sync_at' => now()]);

        return $stored;
    }

    public function updateCampaignStatus(AdCampaign $campaign, string $status): bool
    {
        if ($this->account->needsTokenRefresh()) {
            $this->refreshAccessToken();
        }

        $customerId = $this->account->account_id;
        $campaignId = $campaign->platform_campaign_id;

        $response = Http::withToken($this->account->access_token)
            ->post(
                $this::API_BASE_URL . '/' . $this::API_VERSION . "/customers/{$customerId}/campaigns:mutate",
                [
                    'operations' => [
                        [
                            'update' => [
                                'resource_name' => "customers/{$customerId}/campaigns/{$campaignId}",
                                'status' => $this->mapStatus($status),
                            ],
                            'update_mask' => 'status',
                        ],
                    ],
                ]
            );

        return $response->successful();
    }

    protected function mapStatus(string $status): string
    {
        return match ($status) {
            'active' => 'ENABLED',
            'paused' => 'PAUSED',
            'draft' => 'PAUSED',
            'archived' => 'REMOVED',
            default => 'PAUSED',
        };
    }

    protected function refreshAccessToken(): void
    {
        if (!$this->account->refresh_token) {
            throw new \Exception('No refresh token available');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $this->account->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            Log::error('Google Ads token refresh failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to refresh Google Ads access token');
        }

        $data = $response->json();

        $this->account->updateTokens(
            $data['access_token'],
            $data['refresh_token'] ?? null,
            now()->addSeconds($data['expires_in'] ?? 3600)
        );
    }
}
