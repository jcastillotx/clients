<?php

namespace App\Services\Marketing;

use App\Models\Campaign;
use App\Models\CampaignLink;
use App\Models\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CampaignManagerService
{
    /**
     * @param  array<string,mixed>  $campaignData
     */
    public function createCampaign(Client $client, array $campaignData): Campaign
    {
        /** @var Campaign $campaign */
        $campaign = Campaign::create([
            'client_id' => $client->id,
            'campaign_name' => (string) ($campaignData['campaign_name'] ?? 'New campaign'),
            'campaign_type' => (string) ($campaignData['campaign_type'] ?? 'content'),
            'description' => $campaignData['description'] ?? null,
            'start_date' => $campaignData['start_date'] ?? null,
            'end_date' => $campaignData['end_date'] ?? null,
            'budget' => $campaignData['budget'] ?? null,
            'goals' => is_array($campaignData['goals'] ?? null) ? (array) $campaignData['goals'] : null,
            'target_metrics' => is_array($campaignData['target_metrics'] ?? null) ? (array) $campaignData['target_metrics'] : null,
            'status' => (string) ($campaignData['status'] ?? 'planning'),
            'created_by' => $campaignData['created_by'] ?? null,
            'meta' => Arr::except($campaignData, [
                'campaign_name', 'campaign_type', 'description', 'start_date', 'end_date', 'budget', 'goals', 'target_metrics', 'status', 'created_by',
            ]),
        ]);

        return $campaign;
    }

    /**
     * @return array<string,mixed>
     */
    public function trackCampaignPerformance(Campaign $campaign): array
    {
        // Production: pull from Google Ads / Meta / LinkedIn / email providers.
        // MVP: aggregate from stored campaign_metrics rows.
        $metrics = $campaign->metrics()
            ->orderBy('metric_date')
            ->get()
            ->groupBy('channel')
            ->map(function ($rows) {
                return [
                    'impressions' => (int) $rows->sum('impressions'),
                    'clicks' => (int) $rows->sum('clicks'),
                    'conversions' => (int) $rows->sum('conversions'),
                    'spend' => (float) $rows->sum('spend'),
                    'revenue' => (float) $rows->sum('revenue'),
                ];
            })->all();

        $spend = (float) $campaign->metrics()->sum('spend');
        $revenue = (float) $campaign->metrics()->sum('revenue');
        $roi = $spend > 0 ? round((($revenue - $spend) / $spend) * 100, 2) : null;

        return [
            'campaign_id' => $campaign->id,
            'status' => $campaign->status,
            'by_channel' => $metrics,
            'totals' => [
                'spend' => $spend,
                'revenue' => $revenue,
                'roi_percent' => $roi,
            ],
        ];
    }

    /**
     * Create a tracked campaign link with UTM parameters.
     *
     * @param  array{utm_source?:?string,utm_medium?:?string,utm_campaign?:?string}  $utm
     */
    public function createTrackedLink(Campaign $campaign, string $originalUrl, array $utm = []): CampaignLink
    {
        $utmSource = $utm['utm_source'] ?? 'portal';
        $utmMedium = $utm['utm_medium'] ?? 'campaign';
        $utmCampaign = $utm['utm_campaign'] ?? Str::slug($campaign->campaign_name);

        /** @var CampaignLink $link */
        $link = CampaignLink::create([
            'campaign_id' => $campaign->id,
            'original_url' => $originalUrl,
            'utm_source' => $utmSource,
            'utm_medium' => $utmMedium,
            'utm_campaign' => $utmCampaign,
            'short_url' => null, // placeholder for future shortener integration
            'clicks' => 0,
            'conversions' => 0,
        ]);

        return $link;
    }
}
