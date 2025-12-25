<?php

namespace App\Http\Livewire\Admin\Reports;

use App\Exports\ArrayExport;
use App\Exports\MultiSheetArrayExport;
use App\Models\Payment;
use App\Models\ReportTemplate;
use App\Models\Request as ServiceRequest;
use App\Models\StorageConnection;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class CustomReportBuilder extends Component
{
    public string $name = '';
    /** @var array<int,string> */
    public array $metrics = ['revenue_by_month'];
    public string $previewMetric = 'revenue_by_month';

    public string $from = '';
    public string $to = '';

    public string $schedule = 'none'; // none|daily|weekly|monthly
    public string $recipients = ''; // comma-separated emails

    /** @var array<int,string> */
    public array $headings = [];

    /** @var array<int,array<int,mixed>> */
    public array $rows = [];

    public function mount(): void
    {
        $this->from = now()->subDays(30)->toDateString();
        $this->to = now()->toDateString();
        $this->preview();
    }

    public function updated($property): void
    {
        if (in_array($property, ['metrics', 'previewMetric', 'from', 'to'], true)) {
            $this->preview();
        }
    }

    public function preview(): void
    {
        [$title, $headings, $rows] = $this->buildDataset($this->previewMetric ?: (($this->metrics[0] ?? '') ?: 'revenue_by_month'));
        $this->headings = $headings;
        $this->rows = $rows;
    }

    /**
     * @return array{0:string,1:array<int,string>,2:array<int,array<int,mixed>>}
     */
    protected function buildDataset(string $metric): array
    {
        return match ($metric) {
            'revenue_by_month' => $this->revenueByMonth(),
            'requests_by_status' => $this->requestsByStatus(),
            'storage_usage_by_client' => $this->storageUsageByClient(),
            default => ['Custom report', ['Metric', 'Value'], [[$this->metric, 'unsupported']]],
        };
    }

    /**
     * @return array<int, array{title:string, headings:array<int,string>, rows:array<int,array<int,mixed>>}>
     */
    protected function buildDatasets(): array
    {
        $metrics = array_values(array_unique(array_filter(array_map(fn ($m) => (string) $m, $this->metrics))));
        if (empty($metrics)) {
            $metrics = ['revenue_by_month'];
        }

        $out = [];
        foreach ($metrics as $m) {
            [$title, $headings, $rows] = $this->buildDataset($m);
            $out[] = [
                'title' => $title,
                'headings' => $headings,
                'rows' => $rows,
            ];
        }
        return $out;
    }

    protected function revenueByMonth(): array
    {
        $rows = Payment::query()
            ->where('status', 'succeeded')
            ->whereDate('processed_at', '>=', $this->from)
            ->whereDate('processed_at', '<=', $this->to)
            ->selectRaw("strftime('%Y-%m', processed_at) as ym, SUM(amount) as total")
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();
        if ($rows->isEmpty()) {
            $rows = Payment::query()
                ->where('status', 'succeeded')
                ->whereDate('processed_at', '>=', $this->from)
                ->whereDate('processed_at', '<=', $this->to)
                ->selectRaw("DATE_FORMAT(processed_at, '%Y-%m') as ym, SUM(amount) as total")
                ->groupBy('ym')
                ->orderBy('ym')
                ->get();
        }

        return [
            'Revenue by month',
            ['Month', 'Revenue'],
            $rows->map(fn ($r) => [(string) $r->ym, (float) $r->total])->all(),
        ];
    }

    protected function requestsByStatus(): array
    {
        $rows = ServiceRequest::query()
            ->whereDate('created_at', '>=', $this->from)
            ->whereDate('created_at', '<=', $this->to)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        return [
            'Requests by status',
            ['Status', 'Count'],
            $rows->map(fn ($r) => [(string) $r->status, (int) $r->total])->all(),
        ];
    }

    protected function storageUsageByClient(): array
    {
        $rows = StorageConnection::query()
            ->join('clients', 'clients.id', '=', 'storage_connections.client_id')
            ->selectRaw('clients.company_name as client, SUM(storage_connections.storage_used) as used')
            ->groupBy('clients.company_name')
            ->orderByDesc('used')
            ->get();

        return [
            'Storage usage by client',
            ['Client', 'Used (bytes)'],
            $rows->map(fn ($r) => [(string) $r->client, (int) $r->used])->all(),
        ];
    }

    public function saveTemplate(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'metrics' => ['required', 'array', 'min:1'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date'],
            'schedule' => ['required', 'in:none,daily,weekly,monthly'],
            'recipients' => ['nullable', 'string', 'max:2000'],
        ]);

        $emails = collect(explode(',', $this->recipients))
            ->map(fn ($e) => trim($e))
            ->filter(fn ($e) => $e !== '' && Str::contains($e, '@'))
            ->unique()
            ->values()
            ->all();

        $next = match ($this->schedule) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            default => null,
        };

        ReportTemplate::create([
            'name' => $this->name,
            'created_by' => auth()->id(),
            'config' => [
                'metrics' => array_values(array_unique(array_filter($this->metrics))),
                'from' => $this->from,
                'to' => $this->to,
            ],
            'recipients' => $emails,
            'schedule' => $this->schedule,
            'is_active' => true,
            'next_send_at' => $next,
        ]);

        session()->flash('success', 'Report template saved.');
    }

    public function export(string $format)
    {
        $datasets = $this->buildDatasets();
        $baseName = Str::slug($this->name ?: ($datasets[0]['title'] ?? 'report')) . "-{$this->from}-{$this->to}";
        $format = strtolower($format);

        if ($format === 'csv') {
            // CSV exports the currently previewed metric only.
            [$title, $headings, $rows] = $this->buildDataset($this->previewMetric ?: ($this->metrics[0] ?? 'revenue_by_month'));
            $filename = Str::slug($this->name ?: $title) . "-{$this->from}-{$this->to}.csv";
            return response()->streamDownload(function () use ($headings, $rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, $headings);
                foreach ($rows as $r) fputcsv($out, $r);
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        if ($format === 'xlsx' || $format === 'excel') {
            $filename = $baseName . '.xlsx';
            return Excel::download(new MultiSheetArrayExport($datasets), $filename);
        }

        if ($format === 'pdf') {
            $filename = $baseName . '.pdf';
            $pdf = Pdf::loadView('admin.reports.export-multi-pdf', [
                'title' => $this->name ?: 'Custom report',
                'from' => $this->from,
                'to' => $this->to,
                'datasets' => $datasets,
            ]);
            return response()->streamDownload(fn () => print($pdf->output()), $filename, ['Content-Type' => 'application/pdf']);
        }

        session()->flash('error', 'Unsupported export format.');
        return null;
    }

    public function render()
    {
        return view('livewire.admin.reports.builder')->layout('layouts.admin', ['title' => 'Custom Report Builder']);
    }
}

