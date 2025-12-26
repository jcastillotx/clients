<?php

namespace App\Http\Livewire\Admin\Reports;

use App\Exports\ArrayExport;
use App\Models\StorageConnection;
use App\Models\StorageSyncLog;
use App\Models\SyncedFile;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class StorageReport extends Component
{
    public string $range = 'last_12_months';
    public string $from = '';
    public string $to = '';

    /** @var array<int, array{label:string,value:int}> */
    public array $usageByClient = [];

    /** @var array<int, array{label:string,value:int}> */
    public array $usageByProvider = [];

    /** @var array<int, array{label:string,value:int}> */
    public array $fileTypes = [];

    /** @var array<int, array<string,mixed>> */
    public array $largeFiles = [];

    /** @var array<string,mixed> */
    public array $sync = [];

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
        $clientUsage = StorageConnection::query()
            ->join('clients', 'clients.id', '=', 'storage_connections.client_id')
            ->selectRaw('clients.company_name as client, SUM(storage_connections.storage_used) as used')
            ->groupBy('clients.company_name')
            ->orderByDesc('used')
            ->limit(20)
            ->get();
        $this->usageByClient = $clientUsage->map(fn ($r) => ['label' => (string) $r->client, 'value' => (int) $r->used])->all();

        $provUsage = StorageConnection::query()
            ->selectRaw('provider, SUM(storage_used) as used')
            ->groupBy('provider')
            ->orderByDesc('used')
            ->get();
        $this->usageByProvider = $provUsage->map(fn ($r) => ['label' => (string) $r->provider, 'value' => (int) $r->used])->all();

        // File type distribution (top 10 mime groups)
        $types = SyncedFile::query()
            ->whereDate('synced_at', '>=', $this->from)
            ->whereDate('synced_at', '<=', $this->to)
            ->selectRaw("CASE WHEN mime_type LIKE '%/%' THEN substr(mime_type, 1, instr(mime_type, '/')-1) ELSE COALESCE(mime_type,'unknown') END as grp, COUNT(*) as total")
            ->groupBy('grp')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
        if ($types->isEmpty()) {
            $types = SyncedFile::query()
                ->whereDate('synced_at', '>=', $this->from)
                ->whereDate('synced_at', '<=', $this->to)
                ->selectRaw("IF(LOCATE('/', mime_type) > 0, SUBSTRING_INDEX(mime_type, '/', 1), COALESCE(mime_type,'unknown')) as grp, COUNT(*) as total")
                ->groupBy('grp')
                ->orderByDesc('total')
                ->limit(10)
                ->get();
        }
        $this->fileTypes = $types->map(fn ($r) => ['label' => (string) $r->grp, 'value' => (int) $r->total])->all();

        // Large file alerts (top 20)
        $big = SyncedFile::query()
            ->with('storageConnection.client')
            ->orderByDesc('file_size')
            ->limit(20)
            ->get();
        $this->largeFiles = $big->map(fn (SyncedFile $f) => [
            'client' => $f->storageConnection?->client?->company_name,
            'provider' => $f->storageConnection?->provider,
            'file_name' => $f->file_name,
            'file_size' => (int) $f->file_size,
            'synced_at' => $f->synced_at?->toDateTimeString(),
        ])->all();

        // Sync success rate
        $logs = StorageSyncLog::query()
            ->whereDate('started_at', '>=', $this->from)
            ->whereDate('started_at', '<=', $this->to);
        $total = (int) $logs->count();
        $ok = (int) (clone $logs)->where('status', 'success')->count();
        $rate = $total > 0 ? ($ok / $total) : 0;
        $this->sync = [
            'total' => $total,
            'success' => $ok,
            'rate' => $rate,
        ];

        $this->dispatch('storage-report-updated',
            usageByClient: $this->usageByClient,
            usageByProvider: $this->usageByProvider,
            fileTypes: $this->fileTypes,
        );
    }

    public function export(string $kind, string $format)
    {
        $kind = strtolower($kind);
        $format = strtolower($format);

        if ($kind === 'large_files') {
            $headings = ['Client', 'Provider', 'File name', 'File size (bytes)', 'Synced at'];
            $rows = array_map(fn ($r) => [$r['client'] ?? '', $r['provider'] ?? '', $r['file_name'] ?? '', $r['file_size'] ?? 0, $r['synced_at'] ?? ''], $this->largeFiles);
            return $this->exportRows($headings, $rows, "large-files-{$this->from}-{$this->to}", $format, 'Large file alerts');
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
        return view('livewire.admin.reports.storage', [
            'usageByClient' => $this->usageByClient,
            'usageByProvider' => $this->usageByProvider,
            'fileTypes' => $this->fileTypes,
            'largeFiles' => $this->largeFiles,
            'sync' => $this->sync,
        ])->layout('layouts.admin', ['title' => 'Storage Reports']);
    }
}

