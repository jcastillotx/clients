<?php

namespace App\Http\Livewire\WhiteLabel;

use App\Models\Client;
use App\Models\ClientReportConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class ReportCustomizer extends Component
{
    public ?int $clientId = null;
    public array $visibleMetrics = [];
    public string $reportFrequency = 'monthly';
    public string $deliveryMethod = 'email';
    public string $recipientsCsv = '';

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);
    }

    public function loadClient(): void
    {
        if (!$this->clientId) return;
        $cfg = ClientReportConfig::query()->firstOrNew(['client_id' => $this->clientId]);
        $this->visibleMetrics = (array) ($cfg->visible_metrics ?? []);
        $this->reportFrequency = (string) ($cfg->report_frequency ?? 'monthly');
        $this->deliveryMethod = (string) ($cfg->delivery_method ?? 'email');
        $this->recipientsCsv = implode(', ', (array) ($cfg->recipients ?? []));
    }

    public function save(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        Validator::make([
            'clientId' => $this->clientId,
            'visibleMetrics' => $this->visibleMetrics,
            'reportFrequency' => $this->reportFrequency,
            'deliveryMethod' => $this->deliveryMethod,
        ], [
            'clientId' => ['required', 'integer', 'exists:clients,id'],
            'visibleMetrics' => ['array'],
            'reportFrequency' => ['required', 'in:daily,weekly,monthly,quarterly'],
            'deliveryMethod' => ['required', 'in:email,portal,both'],
        ])->validate();

        $recipients = array_values(array_filter(array_map('trim', explode(',', $this->recipientsCsv))));

        ClientReportConfig::updateOrCreate(
            ['client_id' => (int) $this->clientId],
            [
                'visible_metrics' => array_values(array_filter(array_map('strval', $this->visibleMetrics))),
                'report_frequency' => $this->reportFrequency,
                'delivery_method' => $this->deliveryMethod,
                'recipients' => $recipients ?: null,
            ]
        );

        session()->flash('success', 'Client report config saved.');
    }

    public function availableMetricKeys(): array
    {
        // Matches ReportDataService::filterPayload metricMap keys
        return [
            // financial
            'revenueTrend', 'invoiceAging', 'paymentMethods',
            // requests
            'byStatus', 'byPriority', 'byType', 'sla',
            // performance
            'responseTime', 'resolutionTime', 'monthly',
        ];
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        $clients = Client::query()->orderBy('company_name')->limit(250)->get(['id', 'company_name']);
        return view('livewire.white-label.report-customizer', [
            'clients' => $clients,
            'availableMetrics' => $this->availableMetricKeys(),
        ])->layout('layouts.admin', ['title' => 'Client Report Customizer']);
    }
}

