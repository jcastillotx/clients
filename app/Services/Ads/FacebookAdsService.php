<?php

namespace App\Services\Ads;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookAdsService
{
    private const API_VERSION = 'v18.0';
    private const API_BASE_URL = 'https://graph.facebook.com';

    public function __construct(
        private AdAccount $account
    ) {}

    public function createCampaign(AdCampaign $campaign): array
    {
        if ($this->account->needsTokenRefresh()) {
            $this->refreshAccessToken();
        }

        $accountId = 'act_' . $this->account->account_id;

        $campaignData = [
            'name' => $campaign->name,
            'objective' => $this->mapObjective($campaign->objective),
            'status' => $this->mapStatus($campaign->status),
            'access_token' => $this->account->access_token,
        ];

        if ($campaign->daily_budget) {
            $campaignData['daily_budget'] = $campaign->daily_budget * 100;
        }

        if ($campaign->lifetime_budget) {
            $campaignData['lifetime_budget'] = $campaign->lifetime_budget * 100;
        }

        $response = Http::post(
            $this::API_BASE_URL . '/' . $this::API_VERSION . "/{$accountId}/campaigns",
            $campaignData
        );

        if ($response->failed()) {
            Log::error('Facebook Ads campaign creation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to create Facebook Ads campaign: ' . $response->body());
        }

        return $response->json();
    }

    public function fetchCampaigns(): array
    {
        if ($this->account->needsTokenRefresh()) {
            $this->refreshAccessToken();
        }

        $accountId = 'act_' . $this->account->account_id;

        $response = Http::get(
            $this::API_BASE_URL . '/' . $this::API_VERSION . "/{$accountId}/campaigns",
            [
                'fields' => 'id,name,objective,status,daily_budget,lifetime_budget,start_time,stop_time',
                'access_token' => $this->account->access_token,
            ]
        );

        if ($response->failed()) {
            Log::error('Facebook Ads campaigns fetch failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [];
        }

        return $response->json()['data'] ?? [];
    }

    public function fetchMetrics(AdCampaign $campaign, Carbon $startDate, Carbon $endDate): array
    {
        if ($this->account->needsTokenRefresh()) {
            $this->refreshAccessToken();
        }

        $campaignId = $campaign->platform_campaign_id;

        $response = Http::get(
            $this::API_BASE_URL . '/' . $this::API_VERSION . "/{$campaignId}/insights",
            [
                'time_range' => json_encode([
                    'since' => $startDate->format('Y-m-d'),
                    'until' => $endDate->format('Y-m-d'),
                ]),
                'time_increment' => 1,
                'fields' => 'impressions,clicks,spend,conversions,ctr,cpc,cpm',
                'access_token' => $this->account->access_token,
            ]
        );

        if ($response->failed()) {
            Log::error('Facebook Ads metrics fetch failed', [
                'campaign_id' => $campaignId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [];
        }

        $results = $response->json()['data'] ?? [];

        return $this->storeMetrics($campaign, $results);
    }

    protected function storeMetrics(AdCampaign $campaign, array $results): array
    {
        $stored = [];

        foreach ($results as $row) {
            $date = Carbon::parse($row['date_start']);

            AdMetric::updateOrCreate(
                [
                    'date' => $date,
                    'entity_type' => 'campaign',
                    'entity_id' => $campaign->id,
                ],
                [
                    'client_id' => $campaign->client_id,
                    'impressions' => $row['impressions'] ?? 0,
                    'clicks' => $row['clicks'] ?? 0,
                    'conversions' => $row['conversions'] ?? 0,
                    'spend' => $row['spend'] ?? 0,
                    'platform_metrics' => $row,
                ]
            );

            $stored[] = [
                'date' => $date->format('Y-m-d'),
                'impressions' => $row['impressions'] ?? 0,
                'clicks' => $row['clicks'] ?? 0,
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

        $campaignId = $campaign->platform_campaign_id;

        $response = Http::post(
            $this::API_BASE_URL . '/' . $this::API_VERSION . "/{$campaignId}",
            [
                'status' => $this->mapStatus($status),
                'access_token' => $this->account->access_token,
            ]
        );

        return $response->successful();
    }

    protected function mapObjective(string $objective): string
    {
        return match ($objective) {
            'awareness' => 'BRAND_AWARENESS',
            'consideration' => 'REACH',
            'conversions' => 'CONVERSIONS',
            'traffic' => 'LINK_CLICKS',
            'engagement' => 'POST_ENGAGEMENT',
            'app_installs' => 'APP_INSTALLS',
            'video_views' => 'VIDEO_VIEWS',
            'lead_generation' => 'LEAD_GENERATION',
            'messages' => 'MESSAGES',
            'sales' => 'PRODUCT_CATALOG_SALES',
            default => 'CONVERSIONS',
        };
    }

    protected function mapStatus(string $status): string
    {
        return match ($status) {
            'active' => 'ACTIVE',
            'paused' => 'PAUSED',
            'draft' => 'PAUSED',
            'archived' => 'ARCHIVED',
            default => 'PAUSED',
        };
    }

    protected function refreshAccessToken(): void
    {
        $response = Http::get($this::API_BASE_URL . '/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => config('services.facebook.client_id'),
            'client_secret' => config('services.facebook.client_secret'),
            'fb_exchange_token' => $this->account->access_token,
        ]);

        if ($response->failed()) {
            Log::error('Facebook Ads token refresh failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to refresh Facebook Ads access token');
        }

        $data = $response->json();

        $this->account->updateTokens(
            $data['access_token'],
            null,
            now()->addSeconds($data['expires_in'] ?? 5184000)
        );
    }
}
