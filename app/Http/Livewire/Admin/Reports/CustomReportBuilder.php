<?php

namespace App\Http\Livewire\Admin\Reports;

use App\Exports\ArrayExport;
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
    public string $metric = 'revenue_by_month'; // revenue_by_month|requests_by_status|storage_usage_by_client

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
        if (in_array($property, ['metric', 'from', 'to'], true)) {
            $this->preview();
        }
    }

    public function preview(): void
    {
        [$title, $headings, $rows] = $this->buildDataset();
        $this->headings = $headings;
        $this->rows = $rows;
    }

    /**
     * @return array{0:string,1:array<int,string>,2:array<int,array<int,mixed>>}
     */
    protected function buildDataset(): array
    {
        return match ($this->metric) {
            'revenue_by_month' => $this->revenueByMonth(),
            'requests_by_status' => $this->requestsByStatus(),
            'storage_usage_by_client' => $this->storageUsageByClient(),
            default => ['Custom report', ['Metric', 'Value'], [[$this->metric, 'unsupported']]],
        };
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
            'metric' => ['required', 'string'],
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
                'metric' => $this->metric,
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
        [$title, $headings, $rows] = $this->buildDataset();
        $baseName = Str::slug($this->name ?: $title) . "-{$this->from}-{$this->to}";
        $format = strtolower($format);

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
        return view('livewire.admin.reports.builder')->layout('layouts.admin', ['title' => 'Custom Report Builder']);
    }
}

