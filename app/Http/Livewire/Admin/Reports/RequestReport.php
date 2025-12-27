<?php

namespace App\Http\Livewire\Admin\Reports;

use App\Exports\ArrayExport;
use App\Models\Request as ServiceRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class RequestReport extends Component
{
    public string $range = 'last_12_months';

    public string $from = '';

    public string $to = '';

    /** @var array<string,mixed> */
    public array $kpis = [];

    /** @var array<int, array{label:string,value:int}> */
    public array $volumeByType = [];

    /** @var array<int, array{label:string,value:int}> */
    public array $volumeByStatus = [];

    /** @var array<int, array{label:string,value:int}> */
    public array $volumeByPriority = [];

    /** @var array<int, array{label:string,value:float}> */
    public array $avgCompletionByType = [];

    /** @var array<int, array<string,mixed>> */
    public array $staffProductivity = [];

    /** @var array<int, array<string,mixed>> */
    public array $sla = [];

    /** @var array<int, array<string,mixed>> */
    public array $bottlenecks = [];

    public function mount(): void
    {
        $this->hydrateRange();
        $this->load();
    }

    public function updatedRange(): void
    {
        $this->hydrateRange();
        $this->load();
    }

    public function updatedFrom(): void
    {
        if ($this->range === 'custom') {
            $this->load();
        }
    }

    public function updatedTo(): void
    {
        if ($this->range === 'custom') {
            $this->load();
        }
    }

    protected function hydrateRange(): void
    {
        $today = now()->startOfDay();
        if ($this->range === 'last_12_months') {
            $this->from = $today->copy()->subMonths(11)->startOfMonth()->toDateString();
            $this->to = $today->copy()->endOfMonth()->toDateString();

            return;
        }
        if ($this->range === 'ytd') {
            $this->from = $today->copy()->startOfYear()->toDateString();
            $this->to = $today->copy()->toDateString();

            return;
        }
        if ($this->range === 'this_year') {
            $this->from = $today->copy()->startOfYear()->toDateString();
            $this->to = $today->copy()->endOfYear()->toDateString();

            return;
        }
        if ($this->from === '') {
            $this->from = $today->copy()->subDays(30)->toDateString();
        }
        if ($this->to === '') {
            $this->to = $today->copy()->toDateString();
        }
    }

    public function load(): void
    {
        $q = ServiceRequest::query()
            ->whereDate('created_at', '>=', $this->from)
            ->whereDate('created_at', '<=', $this->to);

        $total = (int) $q->count();
        $completed = (int) (clone $q)->where('status', 'completed')->count();
        $open = (int) (clone $q)->whereNotIn('status', ['completed', 'cancelled'])->count();

        $this->kpis = [
            'total' => $total,
            'completed' => $completed,
            'open' => $open,
        ];

        $this->volumeByType = (clone $q)
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['label' => (string) $r->type, 'value' => (int) $r->total])
            ->values()
            ->all();

        $this->volumeByStatus = (clone $q)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['label' => (string) $r->status, 'value' => (int) $r->total])
            ->values()
            ->all();

        $this->volumeByPriority = (clone $q)
            ->select('priority', DB::raw('COUNT(*) as total'))
            ->groupBy('priority')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['label' => (string) $r->priority, 'value' => (int) $r->total])
            ->values()
            ->all();

        // Avg completion time by type (hours)
        $avg = ServiceRequest::query()
            ->whereNotNull('completed_at')
            ->whereDate('completed_at', '>=', $this->from)
            ->whereDate('completed_at', '<=', $this->to)
            ->select('type', DB::raw('AVG((julianday(completed_at) - julianday(created_at)) * 24.0) as hours'))
            ->groupBy('type')
            ->orderByDesc('hours')
            ->get();
        if ($avg->isEmpty()) {
            $avg = ServiceRequest::query()
                ->whereNotNull('completed_at')
                ->whereDate('completed_at', '>=', $this->from)
                ->whereDate('completed_at', '<=', $this->to)
                ->select('type', DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as hours'))
                ->groupBy('type')
                ->orderByDesc('hours')
                ->get();
        }

        $this->avgCompletionByType = $avg->map(fn ($r) => ['label' => (string) $r->type, 'value' => (float) $r->hours])->values()->all();

        // Staff productivity (completed requests per assignee)
        $prod = ServiceRequest::query()
            ->whereNotNull('assigned_to')
            ->whereNotNull('completed_at')
            ->whereDate('completed_at', '>=', $this->from)
            ->whereDate('completed_at', '<=', $this->to)
            ->select('assigned_to', DB::raw('COUNT(*) as total'))
            ->groupBy('assigned_to')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        $userMap = User::query()->whereIn('id', $prod->pluck('assigned_to')->all())->pluck('name', 'id');
        $this->staffProductivity = $prod->map(fn ($r) => [
            'staff' => (string) ($userMap[$r->assigned_to] ?? ('User #'.$r->assigned_to)),
            'completed' => (int) $r->total,
        ])->values()->all();

        // SLA compliance: due_date exists and completed_at <= due_date
        $slaTotal = ServiceRequest::query()
            ->whereNotNull('due_date')
            ->whereNotNull('completed_at')
            ->whereDate('completed_at', '>=', $this->from)
            ->whereDate('completed_at', '<=', $this->to)
            ->count();
        $slaOk = ServiceRequest::query()
            ->whereNotNull('due_date')
            ->whereNotNull('completed_at')
            ->whereDate('completed_at', '>=', $this->from)
            ->whereDate('completed_at', '<=', $this->to)
            ->whereColumn('completed_at', '<=', 'due_date')
            ->count();
        $slaRate = $slaTotal > 0 ? ($slaOk / $slaTotal) : 0;

        $overdueOpen = ServiceRequest::query()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->count();

        $this->sla = [
            [
                'metric' => 'Completed with due date',
                'value' => $slaTotal,
            ],
            [
                'metric' => 'SLA compliant',
                'value' => $slaOk,
            ],
            [
                'metric' => 'SLA compliance rate',
                'value' => round($slaRate * 100, 1).'%',
            ],
            [
                'metric' => 'Overdue open requests',
                'value' => $overdueOpen,
            ],
        ];

        // Bottleneck analysis (approx): for open requests, compute count + avg age (days) by status.
        $openQ = ServiceRequest::query()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereDate('created_at', '>=', $this->from)
            ->whereDate('created_at', '<=', $this->to);

        $bt = (clone $openQ)
            ->selectRaw('status, COUNT(*) as total, AVG((julianday("now") - julianday(created_at))) as avg_days, MAX((julianday("now") - julianday(created_at))) as max_days')
            ->groupBy('status')
            ->orderByDesc('avg_days')
            ->get();

        if ($bt->isEmpty()) {
            $bt = (clone $openQ)
                ->selectRaw('status, COUNT(*) as total, AVG(TIMESTAMPDIFF(DAY, created_at, NOW())) as avg_days, MAX(TIMESTAMPDIFF(DAY, created_at, NOW())) as max_days')
                ->groupBy('status')
                ->orderByDesc('avg_days')
                ->get();
        }

        $this->bottlenecks = $bt->map(fn ($r) => [
            'status' => (string) $r->status,
            'open' => (int) $r->total,
            'avg_days' => round((float) $r->avg_days, 1),
            'max_days' => round((float) $r->max_days, 1),
        ])->values()->all();

        $this->dispatch('request-report-updated',
            volumeByType: $this->volumeByType,
            volumeByStatus: $this->volumeByStatus,
            volumeByPriority: $this->volumeByPriority,
            avgCompletionByType: $this->avgCompletionByType,
        );
    }

    public function export(string $kind, string $format)
    {
        $kind = strtolower($kind);
        $format = strtolower($format);

        if ($kind === 'staff_productivity') {
            $headings = ['Staff', 'Completed requests'];
            $rows = array_map(fn ($r) => [$r['staff'], $r['completed']], $this->staffProductivity);

            return $this->exportRows($headings, $rows, "staff-productivity-{$this->from}-{$this->to}", $format, 'Staff productivity');
        }

        if ($kind === 'sla') {
            $headings = ['Metric', 'Value'];
            $rows = array_map(fn ($r) => [$r['metric'], $r['value']], $this->sla);

            return $this->exportRows($headings, $rows, "sla-{$this->from}-{$this->to}", $format, 'SLA compliance');
        }

        if ($kind === 'bottlenecks') {
            $headings = ['Status', 'Open', 'Avg age (days)', 'Max age (days)'];
            $rows = array_map(fn ($r) => [$r['status'], $r['open'], $r['avg_days'], $r['max_days']], $this->bottlenecks);

            return $this->exportRows($headings, $rows, "request-bottlenecks-{$this->from}-{$this->to}", $format, 'Request bottleneck analysis');
        }

        session()->flash('error', 'Unknown export.');

        return null;
    }

    protected function exportRows(array $headings, array $rows, string $baseName, string $format, string $title)
    {
        if ($format === 'csv') {
            $filename = $baseName.'.csv';

            return response()->streamDownload(function () use ($headings, $rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, $headings);
                foreach ($rows as $r) {
                    fputcsv($out, $r);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        if ($format === 'xlsx' || $format === 'excel') {
            $filename = $baseName.'.xlsx';

            return Excel::download(new ArrayExport($headings, $rows), $filename);
        }

        if ($format === 'pdf') {
            $filename = $baseName.'.pdf';
            $pdf = Pdf::loadView('admin.reports.export-pdf', [
                'title' => $title,
                'from' => $this->from,
                'to' => $this->to,
                'headings' => $headings,
                'rows' => $rows,
            ]);

            return response()->streamDownload(fn () => print ($pdf->output()), $filename, ['Content-Type' => 'application/pdf']);
        }

        session()->flash('error', 'Unsupported export format.');

        return null;
    }

    public function render()
    {
        return view('livewire.admin.reports.requests', [
            'kpis' => $this->kpis,
            'volumeByType' => $this->volumeByType,
            'volumeByStatus' => $this->volumeByStatus,
            'volumeByPriority' => $this->volumeByPriority,
            'avgCompletionByType' => $this->avgCompletionByType,
            'staffProductivity' => $this->staffProductivity,
            'sla' => $this->sla,
            'bottlenecks' => $this->bottlenecks,
        ])->layout('layouts.admin', ['title' => 'Request Reports']);
    }
}
