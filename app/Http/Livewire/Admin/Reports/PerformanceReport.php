<?php

namespace App\Http\Livewire\Admin\Reports;

use App\Exports\ArrayExport;
use App\Models\Request as ServiceRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class PerformanceReport extends Component
{
    public string $range = 'last_12_months';
    public string $from = '';
    public string $to = '';

    /** @var array<string,mixed> */
    public array $kpis = [];

    /** @var array<int, array{label:string,value:float}> */
    public array $avgResponseByMonth = [];

    /** @var array<int, array{label:string,value:float}> */
    public array $avgResolutionByMonth = [];

    /** @var array<int, array<string,mixed>> */
    public array $staffWorkload = [];

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
        if ($this->range === 'custom') $this->load();
    }

    public function updatedTo(): void
    {
        if ($this->range === 'custom') $this->load();
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
        if ($this->from === '') $this->from = $today->copy()->subDays(30)->toDateString();
        if ($this->to === '') $this->to = $today->copy()->toDateString();
    }

    public function load(): void
    {
        // Response time approximation: created_at -> started_at (hours)
        $avgResponse = ServiceRequest::query()
            ->whereNotNull('started_at')
            ->whereDate('created_at', '>=', $this->from)
            ->whereDate('created_at', '<=', $this->to)
            ->selectRaw("strftime('%Y-%m', created_at) as ym, AVG((julianday(started_at) - julianday(created_at)) * 24.0) as hours")
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();
        if ($avgResponse->isEmpty()) {
            $avgResponse = ServiceRequest::query()
                ->whereNotNull('started_at')
                ->whereDate('created_at', '>=', $this->from)
                ->whereDate('created_at', '<=', $this->to)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, AVG(TIMESTAMPDIFF(HOUR, created_at, started_at)) as hours")
                ->groupBy('ym')
                ->orderBy('ym')
                ->get();
        }
        $this->avgResponseByMonth = $avgResponse->map(fn ($r) => ['label' => (string) $r->ym, 'value' => (float) $r->hours])->values()->all();

        // Resolution time: created_at -> completed_at (hours)
        $avgRes = ServiceRequest::query()
            ->whereNotNull('completed_at')
            ->whereDate('completed_at', '>=', $this->from)
            ->whereDate('completed_at', '<=', $this->to)
            ->selectRaw("strftime('%Y-%m', completed_at) as ym, AVG((julianday(completed_at) - julianday(created_at)) * 24.0) as hours")
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();
        if ($avgRes->isEmpty()) {
            $avgRes = ServiceRequest::query()
                ->whereNotNull('completed_at')
                ->whereDate('completed_at', '>=', $this->from)
                ->whereDate('completed_at', '<=', $this->to)
                ->selectRaw("DATE_FORMAT(completed_at, '%Y-%m') as ym, AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as hours")
                ->groupBy('ym')
                ->orderBy('ym')
                ->get();
        }
        $this->avgResolutionByMonth = $avgRes->map(fn ($r) => ['label' => (string) $r->ym, 'value' => (float) $r->hours])->values()->all();

        $avgResponseOverall = (float) collect($this->avgResponseByMonth)->avg('value');
        $avgResolutionOverall = (float) collect($this->avgResolutionByMonth)->avg('value');

        // Workload distribution: open requests assigned to each staff
        $work = ServiceRequest::query()
            ->whereNotNull('assigned_to')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->select('assigned_to', DB::raw('COUNT(*) as total'))
            ->groupBy('assigned_to')
            ->orderByDesc('total')
            ->limit(20)
            ->get();
        $userMap = User::query()->whereIn('id', $work->pluck('assigned_to')->all())->pluck('name', 'id');
        $this->staffWorkload = $work->map(fn ($r) => [
            'staff' => (string) ($userMap[$r->assigned_to] ?? ('User #' . $r->assigned_to)),
            'open_requests' => (int) $r->total,
        ])->values()->all();

        $this->kpis = [
            'avg_response_hours' => $avgResponseOverall,
            'avg_resolution_hours' => $avgResolutionOverall,
            'satisfaction' => null, // not tracked
        ];

        $this->dispatch('performance-report-updated',
            avgResponseByMonth: $this->avgResponseByMonth,
            avgResolutionByMonth: $this->avgResolutionByMonth,
        );
    }

    public function export(string $kind, string $format)
    {
        $kind = strtolower($kind);
        $format = strtolower($format);

        if ($kind === 'staff_workload') {
            $headings = ['Staff', 'Open requests'];
            $rows = array_map(fn ($r) => [$r['staff'], $r['open_requests']], $this->staffWorkload);
            return $this->exportRows($headings, $rows, "staff-workload-{$this->from}-{$this->to}", $format, 'Staff workload');
        }

        session()->flash('error', 'Unknown export.');
        return null;
    }

    protected function exportRows(array $headings, array $rows, string $baseName, string $format, string $title)
    {
        if ($format === 'csv') {
            $filename = $baseName . '.csv';
            return response()->streamDownload(function () use ($headings, $rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, $headings);
                foreach ($rows as $r) fputcsv($out, $r);
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        if ($format === 'xlsx' || $format === 'excel') {
            $filename = $baseName . '.xlsx';
            return Excel::download(new ArrayExport($headings, $rows), $filename);
        }

        if ($format === 'pdf') {
            $filename = $baseName . '.pdf';
            $pdf = Pdf::loadView('admin.reports.export-pdf', [
                'title' => $title,
                'from' => $this->from,
                'to' => $this->to,
                'headings' => $headings,
                'rows' => $rows,
            ]);
            return response()->streamDownload(fn () => print($pdf->output()), $filename, ['Content-Type' => 'application/pdf']);
        }

        session()->flash('error', 'Unsupported export format.');
        return null;
    }

    public function render()
    {
        return view('livewire.admin.reports.performance', [
            'kpis' => $this->kpis,
            'avgResponseByMonth' => $this->avgResponseByMonth,
            'avgResolutionByMonth' => $this->avgResolutionByMonth,
            'staffWorkload' => $this->staffWorkload,
        ])->layout('layouts.admin', ['title' => 'Performance Reports']);
    }
}

