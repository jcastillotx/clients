<?php

namespace App\Http\Livewire\Admin\Reports;

use App\Exports\ArrayExport;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class ClientReport extends Component
{
    public string $range = 'last_12_months';

    public string $from = '';

    public string $to = '';

    /** @var array<string, mixed> */
    public array $kpis = [];

    /** @var array<int, array{label:string,value:int}> */
    public array $newClientsByMonth = [];

    /** @var array<int, array{label:string,value:int}> */
    public array $clientsByTier = [];

    /** @var array<int, array{label:string,value:int}> */
    public array $clientsByStatus = [];

    /** @var array<int, array<string,mixed>> */
    public array $topClients = [];

    /** @var array<int, array<string,mixed>> */
    public array $lifetimeValue = [];

    /** @var array<int, array<string,mixed>> */
    public array $churnRisk = [];

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
        $totalClients = (int) Client::query()->count();
        $newClients = (int) Client::query()->whereDate('created_at', '>=', $this->from)->whereDate('created_at', '<=', $this->to)->count();

        // Retention approximation: clients created before range start that had any activity within range.
        $existingBefore = Client::query()->whereDate('created_at', '<', $this->from)->pluck('id')->all();
        $activeInRange = [];
        if (! empty($existingBefore)) {
            $activeInRange = ActivityLog::query()
                ->whereIn('client_id', $existingBefore)
                ->whereDate('created_at', '>=', $this->from)
                ->whereDate('created_at', '<=', $this->to)
                ->distinct()
                ->pluck('client_id')
                ->all();
        }
        $retention = ! empty($existingBefore) ? (count($activeInRange) / count($existingBefore)) : 0;

        // Client lifetime value (LTV): total succeeded payments per client (all-time).
        $ltvRows = Payment::query()
            ->where('status', 'succeeded')
            ->join('clients', 'clients.id', '=', 'payments.client_id')
            ->selectRaw('clients.company_name as client, clients.tier as tier, SUM(payments.amount) as ltv')
            ->groupBy('clients.company_name', 'clients.tier')
            ->orderByDesc('ltv')
            ->limit(20)
            ->get();
        $this->lifetimeValue = $ltvRows->map(fn ($r) => [
            'client' => (string) $r->client,
            'tier' => (string) $r->tier,
            'ltv' => (float) $r->ltv,
        ])->all();

        $avgLtv = (float) Payment::query()
            ->where('status', 'succeeded')
            ->selectRaw('AVG(t.total) as avg_ltv')
            ->fromSub(
                Payment::query()
                    ->where('status', 'succeeded')
                    ->selectRaw('client_id, SUM(amount) as total')
                    ->groupBy('client_id'),
                't'
            )
            ->value('avg_ltv');

        $this->kpis = [
            'total_clients' => $totalClients,
            'new_clients' => $newClients,
            'retention_rate' => $retention,
            'avg_ltv' => $avgLtv,
        ];

        // New clients by month
        $driver = DB::connection()->getDriverName();
        $dateExpr = match ($driver) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "to_char(date_trunc('month', created_at), 'YYYY-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        $rows = Client::query()
            ->whereDate('created_at', '>=', $this->from)
            ->whereDate('created_at', '<=', $this->to)
            ->selectRaw("{$dateExpr} as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();
        $this->newClientsByMonth = $rows->map(fn ($r) => ['label' => (string) $r->ym, 'value' => (int) $r->total])->values()->all();

        $this->clientsByTier = Client::query()
            ->select('tier', DB::raw('COUNT(*) as total'))
            ->groupBy('tier')
            ->orderBy('tier')
            ->get()
            ->map(fn ($r) => ['label' => (string) $r->tier, 'value' => (int) $r->total])
            ->values()
            ->all();

        $this->clientsByStatus = Client::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($r) => ['label' => (string) $r->status, 'value' => (int) $r->total])
            ->values()
            ->all();

        // Top clients by revenue + requests count in range
        $rev = Payment::query()
            ->where('status', 'succeeded')
            ->whereDate('processed_at', '>=', $this->from)
            ->whereDate('processed_at', '<=', $this->to)
            ->select('client_id', DB::raw('SUM(amount) as revenue'))
            ->groupBy('client_id');

        $top = Client::query()
            ->leftJoinSub($rev, 'rev', fn ($j) => $j->on('clients.id', '=', 'rev.client_id'))
            ->leftJoin('requests', function ($j) {
                $j->on('requests.client_id', '=', 'clients.id')
                    ->whereNull('requests.deleted_at');
            })
            ->select('clients.id', 'clients.company_name', 'clients.tier', 'clients.status', DB::raw('COALESCE(rev.revenue, 0) as revenue'), DB::raw('COUNT(requests.id) as requests_count'))
            ->groupBy('clients.id', 'clients.company_name', 'clients.tier', 'clients.status', 'rev.revenue')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $this->topClients = $top->map(fn ($r) => [
            'client' => (string) $r->company_name,
            'tier' => (string) $r->tier,
            'status' => (string) $r->status,
            'revenue' => (float) $r->revenue,
            'requests' => (int) $r->requests_count,
        ])->all();

        // Churn risk: no activity for 60+ days
        $cutoff = now()->subDays(60);
        $lastActivity = ActivityLog::query()
            ->select('client_id', DB::raw('MAX(created_at) as last_at'))
            ->groupBy('client_id');

        $risk = Client::query()
            ->leftJoinSub($lastActivity, 'al', fn ($j) => $j->on('clients.id', '=', 'al.client_id'))
            ->select('clients.company_name', 'clients.status', 'clients.tier', 'al.last_at')
            ->where(function ($q) use ($cutoff) {
                $q->whereNull('al.last_at')->orWhere('al.last_at', '<', $cutoff);
            })
            ->whereNull('clients.deleted_at')
            ->orderBy('al.last_at')
            ->limit(25)
            ->get();

        $this->churnRisk = $risk->map(fn ($r) => [
            'client' => (string) $r->company_name,
            'tier' => (string) $r->tier,
            'status' => (string) $r->status,
            'last_activity' => $r->last_at ? (string) $r->last_at : null,
        ])->all();

        $this->dispatch('client-report-updated',
            newClientsByMonth: $this->newClientsByMonth,
            clientsByTier: $this->clientsByTier,
            clientsByStatus: $this->clientsByStatus,
        );
    }

    public function export(string $kind, string $format)
    {
        $kind = strtolower($kind);
        $format = strtolower($format);

        if ($kind === 'top_clients') {
            $headings = ['Client', 'Tier', 'Status', 'Revenue', 'Requests'];
            $rows = array_map(fn ($r) => [$r['client'], $r['tier'], $r['status'], $r['revenue'], $r['requests']], $this->topClients);

            return $this->exportRows($headings, $rows, "top-clients-{$this->from}-{$this->to}", $format, 'Top clients');
        }

        if ($kind === 'churn_risk') {
            $headings = ['Client', 'Tier', 'Status', 'Last activity'];
            $rows = array_map(fn ($r) => [$r['client'], $r['tier'], $r['status'], $r['last_activity'] ?? ''], $this->churnRisk);

            return $this->exportRows($headings, $rows, "churn-risk-{$this->from}-{$this->to}", $format, 'Churn risk');
        }

        if ($kind === 'ltv') {
            $headings = ['Client', 'Tier', 'Lifetime value'];
            $rows = array_map(fn ($r) => [$r['client'], $r['tier'], $r['ltv']], $this->lifetimeValue);

            return $this->exportRows($headings, $rows, "client-ltv-{$this->from}-{$this->to}", $format, 'Client lifetime value');
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
        return view('livewire.admin.reports.clients', [
            'kpis' => $this->kpis,
            'newClientsByMonth' => $this->newClientsByMonth,
            'clientsByTier' => $this->clientsByTier,
            'clientsByStatus' => $this->clientsByStatus,
            'topClients' => $this->topClients,
            'lifetimeValue' => $this->lifetimeValue,
            'churnRisk' => $this->churnRisk,
        ])->layout('layouts.admin', ['title' => 'Client Reports']);
    }
}
