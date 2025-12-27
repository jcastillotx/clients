<?php

namespace App\Http\Livewire\Research;

use App\Models\IndustryMonitor as IndustryMonitorModel;
use App\Services\AI\ResearchAssistantService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class IndustryMonitor extends Component
{
    public string $title = '';
    public string $industry = '';
    public ?string $region = null;
    public string $keywordsCsv = '';
    public string $cadence = 'weekly';

    public ?int $lastReportId = null;
    public array $lastReportPayload = [];
    public ?string $error = null;

    public function createMonitor(): void
    {
        $keywords = array_values(array_filter(array_map('trim', explode(',', $this->keywordsCsv))));

        $m = IndustryMonitorModel::create([
            'title' => $this->title ?: ($this->industry . ' monitor'),
            'client_id' => Auth::user()?->client_id,
            'created_by' => Auth::id(),
            'industry' => $this->industry,
            'region' => $this->region,
            'keywords' => $keywords ?: null,
            'cadence' => $this->cadence,
            'status' => 'active',
        ]);

        $this->runMonitor($m->id);
    }

    public function runMonitor(int $monitorId, ResearchAssistantService $svc = null): void
    {
        $this->error = null;
        $this->lastReportPayload = [];

        try {
            $monitor = IndustryMonitorModel::query()->with('lastReport')->findOrFail($monitorId);
            $report = app(ResearchAssistantService::class)->runIndustryMonitor($monitor);
            $this->lastReportId = $report->id;
            $this->lastReportPayload = $report->payload ?? [];
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function loadLatest(): void
    {
        $monitor = IndustryMonitorModel::query()
            ->where('client_id', Auth::user()?->client_id)
            ->orderByDesc('id')
            ->first();

        if ($monitor && $monitor->lastReport) {
            $this->lastReportId = $monitor->lastReport->id;
            $this->lastReportPayload = $monitor->lastReport->payload ?? [];
        }
    }

    public function render()
    {
        $monitors = IndustryMonitorModel::query()
            ->where('client_id', Auth::user()?->client_id)
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        return view('livewire.research.industry-monitor', [
            'monitors' => $monitors,
        ])->layout('layouts.app', ['title' => 'Industry Monitor']);
    }
}

