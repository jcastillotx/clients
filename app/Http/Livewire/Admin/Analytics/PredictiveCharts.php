<?php

namespace App\Http\Livewire\Admin\Analytics;

use App\Services\AI\PredictiveAnalyticsService;
use Livewire\Component;

class PredictiveCharts extends Component
{
    public int $months = 12;

    public function render(PredictiveAnalyticsService $svc)
    {
        $rev = $svc->forecastRevenue($this->months);
        $vol = $svc->forecastRequestVolume($this->months);
        $resources = $svc->optimizeResourceAllocation();

        return view('livewire.admin.analytics.predictive-charts', [
            'rev' => $rev,
            'vol' => $vol,
            'resources' => $resources,
        ])->layout('layouts.admin', ['title' => 'Predictive Charts']);
    }
}

