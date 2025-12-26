<?php

namespace App\Jobs\Analytics;

use App\Models\AiInsightReport;
use App\Services\AI\PredictiveAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateMonthlyRevenueForecastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('ai');
    }

    public function handle(PredictiveAnalyticsService $svc): void
    {
        $payload = [
            'revenue_forecast_12m' => $svc->forecastRevenue(12),
            'request_volume_12m' => $svc->forecastRequestVolume(12),
        ];

        $narrative = $svc->generateNarrative(
            'Create a monthly revenue forecast summary for the next 12 months, including confidence intervals and key drivers.',
            $payload,
            ['provider' => 'claude']
        );

        AiInsightReport::create([
            'kind' => 'monthly_forecast',
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'payload' => $payload,
            'narrative' => $narrative ?: null,
        ]);
    }
}

