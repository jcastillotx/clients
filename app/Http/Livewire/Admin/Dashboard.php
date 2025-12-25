<?php

namespace App\Http\Livewire\Admin;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Request as ServiceRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public int $activeClients = 0;
    public array $openRequestsByStatus = [];
    public float $outstandingInvoiceAmount = 0.0;
    public float $revenueThisMonth = 0.0;
    public float $revenueLastMonth = 0.0;
    public int $activeContracts = 0;

    public array $requestStatusChart = [];
    public array $revenueTrendChart = [];

    public array $topClients = [];
    public array $overdueInvoices = [];

    public array $recentActivity = [];

    public function mount(): void
    {
        $payload = Cache::remember('admin_dashboard:v1', now()->addMinutes(5), function () {
            $activeClients = (int) Client::query()->where('status', 'active')->count();

            $openRequests = ServiceRequest::query()
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            // Outstanding = sum(invoice.amount) - sum(successful payments)
            $invoiceTotal = (float) Invoice::query()
                ->whereIn('status', ['sent', 'overdue'])
                ->sum('amount');

            $paidTotal = (float) Payment::query()
                ->where('status', 'succeeded')
                ->whereIn('invoice_id', Invoice::query()->whereIn('status', ['sent', 'overdue'])->select('id'))
                ->sum('amount');

            $outstanding = max(0, $invoiceTotal - $paidTotal);

            $thisMonthStart = Carbon::now()->startOfMonth();
            $lastMonthStart = Carbon::now()->subMonthNoOverflow()->startOfMonth();
            $lastMonthEnd = Carbon::now()->subMonthNoOverflow()->endOfMonth();

            $revenueThisMonth = (float) Payment::query()
                ->where('status', 'succeeded')
                ->whereBetween('processed_at', [$thisMonthStart, Carbon::now()])
                ->sum('amount');

            $revenueLastMonth = (float) Payment::query()
                ->where('status', 'succeeded')
                ->whereBetween('processed_at', [$lastMonthStart, $lastMonthEnd])
                ->sum('amount');

            $activeContracts = (int) Contract::query()->where('status', 'active')->count();

            // Request status "funnel" chart (bar)
            $statusKeys = array_keys(config('client-portal.request_statuses', []));
            if (empty($statusKeys)) {
                $statusKeys = ['pending', 'in_review', 'approved', 'in_progress', 'on_hold', 'completed', 'cancelled'];
            }
            $statusCounts = [];
            foreach ($statusKeys as $k) {
                $statusCounts[] = (int) ServiceRequest::query()->where('status', $k)->count();
            }
            $requestStatusChart = [
                'labels' => array_map(fn ($s) => ucfirst(str_replace('_', ' ', (string) $s)), $statusKeys),
                'values' => $statusCounts,
            ];

            // Revenue trend last 6 months
            $months = [];
            for ($i = 5; $i >= 0; $i--) {
                $months[] = Carbon::now()->startOfMonth()->subMonths($i);
            }
            $labels = [];
            $values = [];
            foreach ($months as $m) {
                $start = $m->copy();
                $end = $m->copy()->endOfMonth();
                $labels[] = $start->format('M Y');
                $values[] = (float) Payment::query()
                    ->where('status', 'succeeded')
                    ->whereBetween('processed_at', [$start, $end])
                    ->sum('amount');
            }
            $revenueTrendChart = [
                'labels' => $labels,
                'values' => $values,
            ];

            // Top clients by revenue
            $topClients = Payment::query()
                ->select('client_id', DB::raw('sum(amount) as total'))
                ->where('status', 'succeeded')
                ->groupBy('client_id')
                ->orderByDesc('total')
                ->with('client:id,company_name')
                ->limit(5)
                ->get()
                ->map(fn ($p) => [
                    'client_id' => $p->client_id,
                    'company' => $p->client?->company_name ?? ('Client #' . $p->client_id),
                    'total' => (float) $p->total,
                ])
                ->toArray();

            // Overdue invoices
            $overdueInvoices = Invoice::query()
                ->with('client:id,company_name')
                ->where('status', 'overdue')
                ->orderBy('due_date')
                ->limit(8)
                ->get()
                ->map(fn (Invoice $inv) => [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'client' => $inv->client?->company_name ?? '—',
                    'amount' => (float) $inv->amount,
                    'due_date' => $inv->due_date?->format('Y-m-d'),
                ])
                ->toArray();

            $recentActivity = ActivityLog::query()
                ->with(['user', 'client'])
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (ActivityLog $a) => [
                    'id' => $a->id,
                    'description' => $a->description,
                    'when' => $a->created_at?->diffForHumans(),
                    'user' => $a->user?->name ?? 'System',
                    'client' => $a->client?->company_name ?? ($a->client_id ? ('Client #' . $a->client_id) : null),
                    'log' => $a->log_name,
                ])
                ->toArray();

            return compact(
                'activeClients',
                'openRequests',
                'outstanding',
                'revenueThisMonth',
                'revenueLastMonth',
                'activeContracts',
                'requestStatusChart',
                'revenueTrendChart',
                'topClients',
                'overdueInvoices',
                'recentActivity'
            );
        });

        $this->activeClients = (int) ($payload['activeClients'] ?? 0);
        $this->openRequestsByStatus = (array) ($payload['openRequests'] ?? []);
        $this->outstandingInvoiceAmount = (float) ($payload['outstanding'] ?? 0);
        $this->revenueThisMonth = (float) ($payload['revenueThisMonth'] ?? 0);
        $this->revenueLastMonth = (float) ($payload['revenueLastMonth'] ?? 0);
        $this->activeContracts = (int) ($payload['activeContracts'] ?? 0);
        $this->requestStatusChart = (array) ($payload['requestStatusChart'] ?? ['labels' => [], 'values' => []]);
        $this->revenueTrendChart = (array) ($payload['revenueTrendChart'] ?? ['labels' => [], 'values' => []]);
        $this->topClients = (array) ($payload['topClients'] ?? []);
        $this->overdueInvoices = (array) ($payload['overdueInvoices'] ?? []);
        $this->recentActivity = (array) ($payload['recentActivity'] ?? []);
    }

    public function render()
    {
        return view('livewire.admin.dashboard');
    }
}

