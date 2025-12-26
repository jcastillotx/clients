<?php

namespace App\Jobs\Analytics;

use App\Models\AiInsightReport;
use App\Services\AI\PredictiveAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateWeeklyTrendReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('ai');
    }

    public function handle(PredictiveAnalyticsService $svc): void
    {
        $payload = [
            'revenue_forecast_3m' => $svc->forecastRevenue(3),
            'request_volume_3m' => $svc->forecastRequestVolume(3),
            'resource_allocation' => $svc->optimizeResourceAllocation(),
        ];

        $narrative = $svc->generateNarrative(
            'Create a weekly trends summary. Call out revenue direction, request volume, and staffing bottlenecks.',
            $payload,
            ['provider' => 'openai']
        );

        AiInsightReport::create([
            'kind' => 'weekly_trends',
            'period_start' => now()->subDays(7)->toDateString(),
            'period_end' => now()->toDateString(),
            'payload' => $payload,
            'narrative' => $narrative ?: null,
        ]);
    }
}

