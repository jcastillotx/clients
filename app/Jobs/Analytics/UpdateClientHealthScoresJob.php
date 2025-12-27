<?php

namespace App\Jobs\Analytics;

use App\Models\Client;
use App\Services\AI\PredictiveAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateClientHealthScoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('ai');
    }

    public function handle(PredictiveAnalyticsService $svc): void
    {
        $clients = Client::query()->where('status', 'active')->get();
        foreach ($clients as $c) {
            $svc->generateClientHealthScore($c);
        }
    }
}
