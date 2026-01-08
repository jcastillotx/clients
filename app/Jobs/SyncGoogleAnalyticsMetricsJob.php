<?php

namespace App\Jobs;

use App\Models\AnalyticsAccount;
use App\Services\Analytics\GoogleAnalytics4Service;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncGoogleAnalyticsMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    public function __construct(
        public AnalyticsAccount $account,
        public Carbon $startDate,
        public Carbon $endDate
    ) {}

    public function handle(): void
    {
        if (!$this->account->is_connected) {
            Log::warning('Analytics account not connected', [
                'account_id' => $this->account->id,
                'client_id' => $this->account->client_id,
            ]);
            return;
        }

        try {
            $service = new GoogleAnalytics4Service($this->account);
            $metrics = $service->fetchMetrics($this->startDate, $this->endDate);

            Log::info('Google Analytics metrics synced', [
                'account_id' => $this->account->id,
                'client_id' => $this->account->client_id,
                'metrics_count' => count($metrics),
                'date_range' => [
                    'start' => $this->startDate->format('Y-m-d'),
                    'end' => $this->endDate->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync Google Analytics metrics', [
                'account_id' => $this->account->id,
                'client_id' => $this->account->client_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Google Analytics sync job failed', [
            'account_id' => $this->account->id,
            'client_id' => $this->account->client_id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
