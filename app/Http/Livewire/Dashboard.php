<?php

namespace App\Http\Livewire;

use App\Models\ActivityLog;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Request as ServiceRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public int $activeRequests = 0;
    public int $pendingInvoices = 0;
    public int $activeContracts = 0;

    /** @var \Illuminate\Support\Collection<int, \App\Models\ActivityLog> */
    public Collection $recentActivity;

    /** @var \Illuminate\Support\Collection<int, \App\Models\Invoice> */
    public Collection $upcomingInvoices;

    /** @var \Illuminate\Support\Collection<int, \App\Models\Contract> */
    public Collection $upcomingContracts;

    /** @var array<string, mixed> */
    public array $requestStatusChart = [];

    /** @var array<string, mixed> */
    public array $invoiceTrendChart = [];

    /** @var array<string, mixed> */
    public array $monthlySpendChart = [];

    public function mount(): void
    {
        $user = auth()->user();

        $clientId = $user?->client_id;
        if (!$clientId) {
            $this->recentActivity = collect();
            $this->upcomingInvoices = collect();
            $this->upcomingContracts = collect();
            $this->requestStatusChart = ['labels' => [], 'values' => []];
            $this->invoiceTrendChart = ['labels' => [], 'billed' => [], 'paid' => []];
            $this->monthlySpendChart = ['labels' => [], 'values' => []];
            return;
        }

        $this->activeRequests = ServiceRequest::query()
            ->where('client_id', $clientId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        $this->pendingInvoices = Invoice::query()
            ->where('client_id', $clientId)
            ->whereIn('status', ['sent', 'overdue'])
            ->count();

        $this->activeContracts = Contract::query()
            ->where('client_id', $clientId)
            ->where('status', 'active')
            ->count();

        $this->recentActivity = ActivityLog::query()
            ->where('client_id', $clientId)
            ->with(['user'])
            ->latest()
            ->take(10)
            ->get();

        $this->upcomingInvoices = Invoice::query()
            ->where('client_id', $clientId)
            ->whereIn('status', ['sent', 'overdue'])
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->take(5)
            ->get();

        $this->upcomingContracts = Contract::query()
            ->where('client_id', $clientId)
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->orderBy('end_date')
            ->take(5)
            ->get();

        // -----------------------------------------------------------------
        // Charts
        // -----------------------------------------------------------------
        $statusKeys = array_keys(config('client-portal.request_statuses', []));
        if (empty($statusKeys)) {
            $statusKeys = ['draft', 'pending', 'in_progress', 'in_review', 'completed', 'cancelled'];
        }

        $statusCounts = [];
        foreach ($statusKeys as $status) {
            $statusCounts[] = ServiceRequest::query()
                ->where('client_id', $clientId)
                ->where('status', $status)
                ->count();
        }

        $this->requestStatusChart = [
            'labels' => array_map(fn ($s) => ucfirst(str_replace('_', ' ', (string) $s)), $statusKeys),
            'values' => $statusCounts,
        ];

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[] = Carbon::now()->startOfMonth()->subMonths($i);
        }

        $labels = [];
        $billed = [];
        $paid = [];
        foreach ($months as $monthStart) {
            $start = $monthStart->copy();
            $end = $monthStart->copy()->endOfMonth();

            $labels[] = $start->format('M Y');

            $billed[] = (float) Invoice::query()
                ->where('client_id', $clientId)
                ->whereNotNull('issue_date')
                ->whereBetween('issue_date', [$start, $end])
                ->sum('amount');

            $paid[] = (float) Payment::query()
                ->where('client_id', $clientId)
                ->where('status', 'succeeded')
                ->whereNotNull('processed_at')
                ->whereBetween('processed_at', [$start, $end])
                ->sum('amount');
        }

        $this->invoiceTrendChart = [
            'labels' => $labels,
            'billed' => $billed,
            'paid' => $paid,
        ];

        $this->monthlySpendChart = [
            'labels' => $labels,
            'values' => $paid,
        ];
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}

