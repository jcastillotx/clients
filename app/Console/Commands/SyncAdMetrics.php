<?php

namespace App\Console\Commands;

use App\Jobs\SyncAdMetricsJob;
use App\Models\AdCampaign;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncAdMetrics extends Command
{
    protected $signature = 'ads:sync-metrics
                            {--days=7 : Number of days to sync}
                            {--client= : Sync only for specific client ID}';

    protected $description = 'Sync advertising metrics from connected ad platforms';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $clientId = $this->option('client');

        $startDate = Carbon::now()->subDays($days);
        $endDate = Carbon::now();

        $this->info("Syncing ad metrics from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");

        $campaigns = AdCampaign::whereIn('status', ['active', 'paused'])
            ->whereNotNull('platform_campaign_id')
            ->when($clientId, function ($query, $clientId) {
                $query->where('client_id', $clientId);
            })
            ->with('adAccount')
            ->get();

        $this->info("Found {$campaigns->count()} active campaigns");

        foreach ($campaigns as $campaign) {
            if ($campaign->adAccount && $campaign->adAccount->is_connected) {
                $this->line("  Dispatching sync for campaign: {$campaign->name} ({$campaign->adAccount->platform})");
                SyncAdMetricsJob::dispatch($campaign, $startDate, $endDate);
            }
        }

        $this->info("Dispatched {$campaigns->count()} sync jobs to the queue");

        return self::SUCCESS;
    }
}
