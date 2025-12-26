<?php

namespace App\Http\Livewire;

use App\Models\ActivityLog;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Request as ServiceRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
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

        $payload = Cache::remember(
            "dashboard:client:{$clientId}:v1",
            now()->addMinutes(15),
            function () use ($clientId) {
                $activeRequests = ServiceRequest::query()
                    ->where('client_id', $clientId)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count();

                $pendingInvoices = Invoice::query()
                    ->where('client_id', $clientId)
                    ->whereIn('status', ['sent', 'overdue'])
                    ->count();

                $activeContracts = Contract::query()
                    ->where('client_id', $clientId)
                    ->where('status', 'active')
                    ->count();

                $recentActivityIds = ActivityLog::query()
                    ->where('client_id', $clientId)
                    ->latest()
                    ->take(10)
                    ->pluck('id')
                    ->all();

                $upcomingInvoiceIds = Invoice::query()
                    ->where('client_id', $clientId)
                    ->whereIn('status', ['sent', 'overdue'])
                    ->whereNotNull('due_date')
                    ->orderBy('due_date')
                    ->take(5)
                    ->pluck('id')
                    ->all();

                $upcomingContractIds = Contract::query()
                    ->where('client_id', $clientId)
                    ->where('status', 'active')
                    ->whereNotNull('end_date')
                    ->orderBy('end_date')
                    ->take(5)
                    ->pluck('id')
                    ->all();

                // Charts
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

                $requestStatusChart = [
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

                $invoiceTrendChart = [
                    'labels' => $labels,
                    'billed' => $billed,
                    'paid' => $paid,
                ];

                $monthlySpendChart = [
                    'labels' => $labels,
                    'values' => $paid,
                ];

                return [
                    'activeRequests' => $activeRequests,
                    'pendingInvoices' => $pendingInvoices,
                    'activeContracts' => $activeContracts,
                    'recentActivityIds' => $recentActivityIds,
                    'upcomingInvoiceIds' => $upcomingInvoiceIds,
                    'upcomingContractIds' => $upcomingContractIds,
                    'requestStatusChart' => $requestStatusChart,
                    'invoiceTrendChart' => $invoiceTrendChart,
                    'monthlySpendChart' => $monthlySpendChart,
                ];
            }
        );

        $this->activeRequests = (int) ($payload['activeRequests'] ?? 0);
        $this->pendingInvoices = (int) ($payload['pendingInvoices'] ?? 0);
        $this->activeContracts = (int) ($payload['activeContracts'] ?? 0);

        $this->requestStatusChart = (array) ($payload['requestStatusChart'] ?? ['labels' => [], 'values' => []]);
        $this->invoiceTrendChart = (array) ($payload['invoiceTrendChart'] ?? ['labels' => [], 'billed' => [], 'paid' => []]);
        $this->monthlySpendChart = (array) ($payload['monthlySpendChart'] ?? ['labels' => [], 'values' => []]);

        $activityIds = (array) ($payload['recentActivityIds'] ?? []);
        $invoiceIds = (array) ($payload['upcomingInvoiceIds'] ?? []);
        $contractIds = (array) ($payload['upcomingContractIds'] ?? []);

        $this->recentActivity = $activityIds
            ? ActivityLog::query()->whereIn('id', $activityIds)->with(['user'])->latest()->get()
            : collect();

        $this->upcomingInvoices = $invoiceIds
            ? Invoice::query()->whereIn('id', $invoiceIds)->orderBy('due_date')->get()
            : collect();

        $this->upcomingContracts = $contractIds
            ? Contract::query()->whereIn('id', $contractIds)->orderBy('end_date')->get()
            : collect();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}

