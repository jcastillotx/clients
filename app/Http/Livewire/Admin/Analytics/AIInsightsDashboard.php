<?php

namespace App\Http\Livewire\Admin\Analytics;

use App\Models\AiInsightReport;
use App\Models\AnomalyAlert;
use App\Models\ClientHealthSnapshot;
use App\Services\AI\PredictiveAnalyticsService;
use Livewire\Component;

class AIInsightsDashboard extends Component
{
    public function render(PredictiveAnalyticsService $svc)
    {
        $latestWeekly = AiInsightReport::query()->where('kind', 'weekly_trends')->orderByDesc('id')->first();
        $latestMonthly = AiInsightReport::query()->where('kind', 'monthly_forecast')->orderByDesc('id')->first();
        $latestQuarterly = AiInsightReport::query()->where('kind', 'quarterly_bi')->orderByDesc('id')->first();

        $alerts = AnomalyAlert::query()
            ->whereNull('resolved_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $atRisk = ClientHealthSnapshot::query()
            ->with('client')
            ->orderByDesc('computed_at')
            ->limit(300)
            ->get()
            ->groupBy('client_id')
            ->map(fn ($rows) => $rows->sortByDesc('computed_at')->first())
            ->values()
            ->sortBy('score')
            ->take(10)
            ->values();

        $rev = $svc->forecastRevenue(6);

        return view('livewire.admin.analytics.ai-insights', [
            'latestWeekly' => $latestWeekly,
            'latestMonthly' => $latestMonthly,
            'latestQuarterly' => $latestQuarterly,
            'alerts' => $alerts,
            'atRisk' => $atRisk,
            'revenueForecast' => $rev['forecast'] ?? [],
        ])->layout('layouts.admin', ['title' => 'AI Insights']);
    }
}

