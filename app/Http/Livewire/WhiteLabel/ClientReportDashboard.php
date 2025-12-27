<?php

namespace App\Http\Livewire\WhiteLabel;

use App\Models\ClientReportConfig;
use App\Models\WhiteLabelConfig;
use App\Services\AdminReports\ReportDataService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ClientReportDashboard extends Component
{
    public function render(ReportDataService $reports)
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);
        abort_unless($user->client_id, 403);

        $cfg = WhiteLabelConfig::query()->where('client_id', $user->client_id)->where('is_active', true)->first();
        $reportCfg = ClientReportConfig::query()->where('client_id', $user->client_id)->first();

        // Minimal, simplified dashboard: requests + performance + financial (filtered)
        $metrics = (array) ($reportCfg?->visible_metrics ?? []);
        if (empty($metrics)) {
            $metrics = ['byStatus', 'sla', 'revenueTrend'];
        }

        $payload = $reports->build('requests', ['metrics' => $metrics]);

        return view('livewire.white-label.client-report-dashboard', [
            'whiteLabel' => $cfg,
            'reportCfg' => $reportCfg,
            'payload' => $payload,
        ])->layout('layouts.app', ['title' => 'Reports']);
    }
}

