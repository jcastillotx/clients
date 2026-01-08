<?php

namespace App\Jobs;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Services\Ads\FacebookAdsService;
use App\Services\Ads\GoogleAdsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAdMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    public function __construct(
        public AdCampaign $campaign,
        public Carbon $startDate,
        public Carbon $endDate
    ) {}

    public function handle(): void
    {
        $account = $this->campaign->adAccount;

        if (!$account->is_connected) {
            Log::warning('Ad account not connected', [
                'campaign_id' => $this->campaign->id,
                'account_id' => $account->id,
                'platform' => $account->platform,
            ]);
            return;
        }

        try {
            $service = match ($account->platform) {
                'google_ads' => new GoogleAdsService($account),
                'facebook_ads', 'instagram_ads' => new FacebookAdsService($account),
                default => null,
            };

            if (!$service) {
                Log::warning('Unsupported ad platform', [
                    'campaign_id' => $this->campaign->id,
                    'platform' => $account->platform,
                ]);
                return;
            }

            $metrics = $service->fetchMetrics($this->campaign, $this->startDate, $this->endDate);

            Log::info('Ad metrics synced', [
                'campaign_id' => $this->campaign->id,
                'account_id' => $account->id,
                'platform' => $account->platform,
                'metrics_count' => count($metrics),
                'date_range' => [
                    'start' => $this->startDate->format('Y-m-d'),
                    'end' => $this->endDate->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync ad metrics', [
                'campaign_id' => $this->campaign->id,
                'account_id' => $account->id,
                'platform' => $account->platform,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Ad metrics sync job failed', [
            'campaign_id' => $this->campaign->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
