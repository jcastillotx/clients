<?php

namespace App\Console\Commands;

use App\Jobs\SyncGoogleAnalyticsMetricsJob;
use App\Jobs\SyncSocialMediaAnalyticsJob;
use App\Models\AnalyticsAccount;
use App\Models\SocialAccount;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncAllAnalytics extends Command
{
    protected $signature = 'analytics:sync-all
                            {--days=7 : Number of days to sync}
                            {--client= : Sync only for specific client ID}';

    protected $description = 'Sync analytics data from Google Analytics and social media platforms';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $clientId = $this->option('client');

        $startDate = Carbon::now()->subDays($days);
        $endDate = Carbon::now();

        $this->info("Syncing analytics data from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");

        $gaAccounts = AnalyticsAccount::where('platform', 'google_analytics_4')
            ->where('is_connected', true)
            ->when($clientId, function ($query, $clientId) {
                $query->where('client_id', $clientId);
            })
            ->get();

        $this->info("Found {$gaAccounts->count()} Google Analytics accounts");

        foreach ($gaAccounts as $account) {
            $this->line("  Dispatching sync for client {$account->client_id} - {$account->property_name}");
            SyncGoogleAnalyticsMetricsJob::dispatch($account, $startDate, $endDate);
        }

        $socialAccounts = SocialAccount::where('is_connected', true)
            ->whereIn('platform', ['facebook', 'instagram', 'linkedin', 'twitter'])
            ->when($clientId, function ($query, $clientId) {
                $query->where('client_id', $clientId);
            })
            ->get();

        $this->info("Found {$socialAccounts->count()} social media accounts");

        foreach ($socialAccounts as $account) {
            $this->line("  Dispatching sync for client {$account->client_id} - {$account->platform}");
            SyncSocialMediaAnalyticsJob::dispatch($account, $startDate, $endDate);
        }

        $totalJobs = $gaAccounts->count() + $socialAccounts->count();
        $this->info("Dispatched {$totalJobs} sync jobs to the queue");

        return self::SUCCESS;
    }
}
