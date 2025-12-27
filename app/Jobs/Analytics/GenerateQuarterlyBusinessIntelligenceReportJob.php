<?php

namespace App\Jobs\Analytics;

use App\Models\AiInsightReport;
use App\Services\AI\PredictiveAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateQuarterlyBusinessIntelligenceReportJob implements ShouldQueue
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
            'resource_allocation' => $svc->optimizeResourceAllocation(),
            'anomalies' => array_map(fn ($a) => [
                'type' => $a->type,
                'severity' => $a->severity,
                'client_id' => $a->client_id,
                'title' => $a->title,
                'created_at' => $a->created_at?->toDateTimeString(),
            ], $svc->detectAnomalies()),
        ];

        $narrative = $svc->generateNarrative(
            'Create a quarterly business intelligence report. Include key trends, risks, and recommendations.',
            $payload,
            ['provider' => 'claude']
        );

        AiInsightReport::create([
            'kind' => 'quarterly_bi',
            'period_start' => now()->subMonths(3)->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'payload' => $payload,
            'narrative' => $narrative ?: null,
        ]);
    }
}
