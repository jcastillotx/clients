<?php

namespace App\Jobs;

use App\Models\SocialAccount;
use App\Services\Analytics\SocialMediaAnalyticsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncSocialMediaAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    public function __construct(
        public SocialAccount $account,
        public Carbon $startDate,
        public Carbon $endDate
    ) {}

    public function handle(): void
    {
        if (!$this->account->is_connected) {
            Log::warning('Social account not connected', [
                'account_id' => $this->account->id,
                'client_id' => $this->account->client_id,
                'platform' => $this->account->platform,
            ]);
            return;
        }

        try {
            $service = new SocialMediaAnalyticsService($this->account);
            $metrics = $service->fetchMetrics($this->startDate, $this->endDate);

            Log::info('Social media analytics synced', [
                'account_id' => $this->account->id,
                'client_id' => $this->account->client_id,
                'platform' => $this->account->platform,
                'metrics_count' => count($metrics),
                'date_range' => [
                    'start' => $this->startDate->format('Y-m-d'),
                    'end' => $this->endDate->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync social media analytics', [
                'account_id' => $this->account->id,
                'client_id' => $this->account->client_id,
                'platform' => $this->account->platform,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Social media analytics sync job failed', [
            'account_id' => $this->account->id,
            'client_id' => $this->account->client_id,
            'platform' => $this->account->platform,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
