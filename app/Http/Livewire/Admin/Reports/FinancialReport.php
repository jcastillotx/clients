<?php

namespace App\Http\Livewire\Admin\Reports;

use App\Exports\ArrayExport;
use App\Models\Invoice;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class FinancialReport extends Component
{
    public string $range = 'last_12_months'; // last_12_months|ytd|this_year|custom
    public string $from = '';
    public string $to = '';
    public string $revenueGroup = 'month'; // month|quarter|year

    /** @var array<string, mixed> */
    public array $kpis = [];

    /** @var array<int, array{label:string,value:float}> */
    public array $revenueByPeriod = [];

    /** @var array<int, array{label:string,value:float}> */
    public array $revenueByTier = [];

    /** @var array<int, array{label:string,value:float}> */
    public array $revenueByServiceType = [];

    /** @var array<int, array{label:string,value:float}> */
    public array $paymentMethods = [];

    /** @var array<int, array<string, mixed>> */
    public array $invoiceAging = [];

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

    public function updatedRevenueGroup(): void
    {
        $this->load();
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

        // custom: keep user inputs
        if ($this->from === '') {
            $this->from = $today->copy()->subDays(30)->toDateString();
        }
        if ($this->to === '') {
            $this->to = $today->copy()->toDateString();
        }
    }

    protected function paymentsQuery(): Builder
    {
        return Payment::query()
            ->where('status', 'succeeded')
            ->whereDate('processed_at', '>=', $this->from)
            ->whereDate('processed_at', '<=', $this->to);
    }

    public function load(): void
    {
        // Revenue totals
        $totalRevenue = (float) $this->paymentsQuery()->sum('amount');
        $refunds = (float) Payment::query()
            ->where('status', 'refunded')
            ->whereDate('processed_at', '>=', $this->from)
            ->whereDate('processed_at', '<=', $this->to)
            ->sum('amount');

        $outstanding = (float) Invoice::query()
            ->whereIn('status', ['sent', 'overdue'])
            ->withSum(['payments as total_paid' => fn ($p) => $p->where('status', 'succeeded')], 'amount')
            ->get()
            ->sum(fn (Invoice $i) => max(0, (float) $i->amount - (float) ($i->total_paid ?? 0)));

        $this->kpis = [
            'revenue' => $totalRevenue,
            'refunds' => $refunds,
            'net_revenue' => max(0, $totalRevenue - $refunds),
            'outstanding' => $outstanding,
        ];

        // Revenue by period (month/quarter/year)
        [$labelExprSqlite, $labelExprMysql, $alias] = match ($this->revenueGroup) {
            'year' => ["strftime('%Y', processed_at)", "DATE_FORMAT(processed_at, '%Y')", 'period'],
            'quarter' => [
                "(strftime('%Y', processed_at) || '-Q' || (((CAST(strftime('%m', processed_at) AS INTEGER)-1)/3)+1))",
                "CONCAT(YEAR(processed_at), '-Q', QUARTER(processed_at))",
                'period'
            ],
            default => ["strftime('%Y-%m', processed_at)", "DATE_FORMAT(processed_at, '%Y-%m')", 'period'],
        };

        $rows = $this->paymentsQuery()
            ->selectRaw("$labelExprSqlite as $alias, SUM(amount) as total")
            ->groupBy($alias)
            ->orderBy($alias)
            ->get();
        if ($rows->isEmpty()) {
            $rows = $this->paymentsQuery()
                ->selectRaw("$labelExprMysql as $alias, SUM(amount) as total")
                ->groupBy($alias)
                ->orderBy($alias)
                ->get();
        }

        $this->revenueByPeriod = $rows->map(fn ($r) => [
            'label' => (string) ($r->$alias ?? ''),
            'value' => (float) $r->total,
        ])->values()->all();

        // Revenue by tier
        $tierRows = $this->paymentsQuery()
            ->join('clients', 'clients.id', '=', 'payments.client_id')
            ->select('clients.tier', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('clients.tier')
            ->orderBy('clients.tier')
            ->get();

        $this->revenueByTier = $tierRows->map(fn ($r) => [
            'label' => (string) $r->tier,
            'value' => (float) $r->total,
        ])->values()->all();

        // Revenue by service type (via invoice->request->type)
        $svcRows = $this->paymentsQuery()
            ->leftJoin('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->leftJoin('requests', 'requests.id', '=', 'invoices.request_id')
            ->selectRaw("COALESCE(requests.type, 'unlinked') as service_type, SUM(payments.amount) as total")
            ->groupBy('service_type')
            ->orderByDesc('total')
            ->get();

        $this->revenueByServiceType = $svcRows->map(fn ($r) => [
            'label' => (string) $r->service_type,
            'value' => (float) $r->total,
        ])->values()->all();

        // Payment method breakdown
        $pm = $this->paymentsQuery()
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();
        $this->paymentMethods = $pm->map(fn ($r) => [
            'label' => (string) $r->payment_method,
            'value' => (float) $r->total,
        ])->values()->all();

        // Invoice aging (unpaid only)
        $today = now()->startOfDay();
        $unpaid = Invoice::query()
            ->whereIn('status', ['sent', 'overdue'])
            ->with('client')
            ->withSum(['payments as total_paid' => fn ($p) => $p->where('status', 'succeeded')], 'amount')
            ->get();

        $buckets = [
            '0-30' => [0, 30],
            '31-60' => [31, 60],
            '61-90' => [61, 90],
            '90+' => [91, 100000],
        ];

        $byClient = [];
        foreach ($unpaid as $inv) {
            $due = Carbon::parse($inv->due_date)->startOfDay();
            $days = $due->diffInDays($today, false); // overdue positive
            if ($days <= 0) {
                continue; // not yet due
            }

            $balance = max(0, (float) $inv->amount - (float) ($inv->total_paid ?? 0));
            if ($balance <= 0) continue;

            $bucket = '90+';
            foreach ($buckets as $k => [$min, $max]) {
                if ($days >= $min && $days <= $max) {
                    $bucket = $k;
                    break;
                }
            }

            $cid = (int) $inv->client_id;
            $byClient[$cid]['client'] = $inv->client?->company_name ?? ('Client #' . $cid);
            $byClient[$cid]['0-30'] = ($byClient[$cid]['0-30'] ?? 0) + ($bucket === '0-30' ? $balance : 0);
            $byClient[$cid]['31-60'] = ($byClient[$cid]['31-60'] ?? 0) + ($bucket === '31-60' ? $balance : 0);
            $byClient[$cid]['61-90'] = ($byClient[$cid]['61-90'] ?? 0) + ($bucket === '61-90' ? $balance : 0);
            $byClient[$cid]['90+'] = ($byClient[$cid]['90+'] ?? 0) + ($bucket === '90+' ? $balance : 0);
            $byClient[$cid]['total'] = ($byClient[$cid]['total'] ?? 0) + $balance;
        }

        $this->invoiceAging = collect($byClient)->values()->sortByDesc('total')->values()->all();

        $this->dispatch('financial-report-updated',
            revenueByPeriod: $this->revenueByPeriod,
            revenueByTier: $this->revenueByTier,
            revenueByServiceType: $this->revenueByServiceType,
            paymentMethods: $this->paymentMethods,
        );
    }

    public function export(string $kind, string $format)
    {
        $kind = strtolower($kind);
        $format = strtolower($format);

        if ($kind === 'revenue_month') {
            $label = match ($this->revenueGroup) {
                'year' => 'Year',
                'quarter' => 'Quarter',
                default => 'Month',
            };
            $headings = [$label, 'Revenue'];
            $rows = array_map(fn ($r) => [$r['label'], $r['value']], $this->revenueByPeriod);
            $suffix = match ($this->revenueGroup) {
                'year' => 'year',
                'quarter' => 'quarter',
                default => 'month',
            };
            return $this->exportRows($headings, $rows, "revenue-by-{$suffix}-{$this->from}-{$this->to}", $format);
        }

        if ($kind === 'invoice_aging') {
            $headings = ['Client', '0-30', '31-60', '61-90', '90+', 'Total'];
            $rows = array_map(fn ($r) => [
                $r['client'] ?? '',
                (float) ($r['0-30'] ?? 0),
                (float) ($r['31-60'] ?? 0),
                (float) ($r['61-90'] ?? 0),
                (float) ($r['90+'] ?? 0),
                (float) ($r['total'] ?? 0),
            ], $this->invoiceAging);
            return $this->exportRows($headings, $rows, "invoice-aging-{$this->from}-{$this->to}", $format, [
                'title' => 'Invoice aging report',
            ]);
        }

        session()->flash('error', 'Unknown export.');
        return null;
    }

    protected function exportRows(array $headings, array $rows, string $baseName, string $format, array $pdfMeta = [])
    {
        if ($format === 'csv') {
            $filename = $baseName . '.csv';
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
            $filename = $baseName . '.xlsx';
            return Excel::download(new ArrayExport($headings, $rows), $filename);
        }

        if ($format === 'pdf') {
            $filename = $baseName . '.pdf';
            $pdf = Pdf::loadView('admin.reports.export-pdf', [
                'title' => (string) ($pdfMeta['title'] ?? 'Report'),
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
        return view('livewire.admin.reports.financial', [
            'kpis' => $this->kpis,
            'revenueByPeriod' => $this->revenueByPeriod,
            'revenueByTier' => $this->revenueByTier,
            'revenueByServiceType' => $this->revenueByServiceType,
            'paymentMethods' => $this->paymentMethods,
            'invoiceAging' => $this->invoiceAging,
        ])->layout('layouts.admin', ['title' => 'Financial Reports']);
    }
}

