<?php

namespace App\Http\Livewire;

use App\Models\ActivityLog;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Request as ServiceRequest;
use App\Models\SupportTicket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class Dashboard extends Component
{
    public int $activeRequests = 0;

    public int $pendingInvoices = 0;

    public int $activeContracts = 0;

    // New dashboard stats
    public float $totalRevenue = 0;

    public float $revenueChange = 0;

    public int $totalOrders = 0;

    public float $ordersChange = 0;

    public int $openTickets = 0;

    public float $ticketsChange = 0;

    /** @var array<int, float> */
    public array $revenueSparkline = [];

    /** @var array<int, int> */
    public array $ordersSparkline = [];

    /** @var array<int, int> */
    public array $ticketsSparkline = [];

    /** @var \Illuminate\Support\Collection<int, \App\Models\SupportTicket> */
    public Collection $recentTickets;

    /** @var \Illuminate\Support\Collection<int, \App\Models\Request> */
    public Collection $recentOrders;

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

    public string $selectedCurrency = 'USD';

    public function mount(): void
    {
        $user = auth()->user();

        $clientId = $user?->client_id;
        if (! $clientId) {
            $this->recentActivity = collect();
            $this->recentTickets = collect();
            $this->recentOrders = collect();
            $this->upcomingInvoices = collect();
            $this->upcomingContracts = collect();
            $this->requestStatusChart = ['labels' => [], 'values' => []];
            $this->invoiceTrendChart = ['labels' => [], 'billed' => [], 'paid' => []];
            $this->monthlySpendChart = ['labels' => [], 'values' => []];
            $this->revenueSparkline = [];
            $this->ordersSparkline = [];
            $this->ticketsSparkline = [];

            return;
        }

        $payload = Cache::remember(
            "dashboard:client:{$clientId}:v2",
            now()->addMinutes(15),
            function () use ($clientId) {
                $now = Carbon::now();
                $thirtyDaysAgo = $now->copy()->subDays(30);
                $sixtyDaysAgo = $now->copy()->subDays(60);

                // Active requests (orders)
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

                // Revenue calculations (last 30 days vs previous 30 days)
                $currentRevenue = (float) Payment::query()
                    ->where('client_id', $clientId)
                    ->where('status', 'succeeded')
                    ->where('processed_at', '>=', $thirtyDaysAgo)
                    ->sum('amount');

                $previousRevenue = (float) Payment::query()
                    ->where('client_id', $clientId)
                    ->where('status', 'succeeded')
                    ->whereBetween('processed_at', [$sixtyDaysAgo, $thirtyDaysAgo])
                    ->sum('amount');

                $revenueChange = $previousRevenue > 0
                    ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1)
                    : ($currentRevenue > 0 ? 100 : 0);

                // Orders calculations (service requests)
                $currentOrders = ServiceRequest::query()
                    ->where('client_id', $clientId)
                    ->where('created_at', '>=', $thirtyDaysAgo)
                    ->count();

                $previousOrders = ServiceRequest::query()
                    ->where('client_id', $clientId)
                    ->whereBetween('created_at', [$sixtyDaysAgo, $thirtyDaysAgo])
                    ->count();

                $ordersChange = $previousOrders > 0
                    ? round((($currentOrders - $previousOrders) / $previousOrders) * 100, 1)
                    : ($currentOrders > 0 ? 100 : 0);

                // Open tickets count
                $openTickets = SupportTicket::query()
                    ->where('client_id', $clientId)
                    ->open()
                    ->count();

                $currentTickets = SupportTicket::query()
                    ->where('client_id', $clientId)
                    ->where('created_at', '>=', $thirtyDaysAgo)
                    ->count();

                $previousTickets = SupportTicket::query()
                    ->where('client_id', $clientId)
                    ->whereBetween('created_at', [$sixtyDaysAgo, $thirtyDaysAgo])
                    ->count();

                $ticketsChange = $previousTickets > 0
                    ? round((($currentTickets - $previousTickets) / $previousTickets) * 100, 1)
                    : ($currentTickets > 0 ? 100 : 0);

                // Sparkline data (last 7 days for mini charts)
                $revenueSparkline = [];
                $ordersSparkline = [];
                $ticketsSparkline = [];

                for ($i = 6; $i >= 0; $i--) {
                    $dayStart = $now->copy()->subDays($i)->startOfDay();
                    $dayEnd = $now->copy()->subDays($i)->endOfDay();

                    $revenueSparkline[] = (float) Payment::query()
                        ->where('client_id', $clientId)
                        ->where('status', 'succeeded')
                        ->whereBetween('processed_at', [$dayStart, $dayEnd])
                        ->sum('amount');

                    $ordersSparkline[] = ServiceRequest::query()
                        ->where('client_id', $clientId)
                        ->whereBetween('created_at', [$dayStart, $dayEnd])
                        ->count();

                    $ticketsSparkline[] = SupportTicket::query()
                        ->where('client_id', $clientId)
                        ->whereBetween('created_at', [$dayStart, $dayEnd])
                        ->count();
                }

                $recentActivityIds = ActivityLog::query()
                    ->where('client_id', $clientId)
                    ->latest()
                    ->take(10)
                    ->pluck('id')
                    ->all();

                $recentTicketIds = SupportTicket::query()
                    ->where('client_id', $clientId)
                    ->open()
                    ->latest()
                    ->take(5)
                    ->pluck('id')
                    ->all();

                $recentOrderIds = ServiceRequest::query()
                    ->where('client_id', $clientId)
                    ->latest()
                    ->take(5)
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
                    'totalRevenue' => $currentRevenue,
                    'revenueChange' => $revenueChange,
                    'totalOrders' => $activeRequests,
                    'ordersChange' => $ordersChange,
                    'openTickets' => $openTickets,
                    'ticketsChange' => $ticketsChange,
                    'revenueSparkline' => $revenueSparkline,
                    'ordersSparkline' => $ordersSparkline,
                    'ticketsSparkline' => $ticketsSparkline,
                    'recentActivityIds' => $recentActivityIds,
                    'recentTicketIds' => $recentTicketIds,
                    'recentOrderIds' => $recentOrderIds,
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

        // New dashboard stats
        $this->totalRevenue = (float) ($payload['totalRevenue'] ?? 0);
        $this->revenueChange = (float) ($payload['revenueChange'] ?? 0);
        $this->totalOrders = (int) ($payload['totalOrders'] ?? 0);
        $this->ordersChange = (float) ($payload['ordersChange'] ?? 0);
        $this->openTickets = (int) ($payload['openTickets'] ?? 0);
        $this->ticketsChange = (float) ($payload['ticketsChange'] ?? 0);
        $this->revenueSparkline = (array) ($payload['revenueSparkline'] ?? []);
        $this->ordersSparkline = (array) ($payload['ordersSparkline'] ?? []);
        $this->ticketsSparkline = (array) ($payload['ticketsSparkline'] ?? []);

        $this->requestStatusChart = (array) ($payload['requestStatusChart'] ?? ['labels' => [], 'values' => []]);
        $this->invoiceTrendChart = (array) ($payload['invoiceTrendChart'] ?? ['labels' => [], 'billed' => [], 'paid' => []]);
        $this->monthlySpendChart = (array) ($payload['monthlySpendChart'] ?? ['labels' => [], 'values' => []]);

        $activityIds = (array) ($payload['recentActivityIds'] ?? []);
        $ticketIds = (array) ($payload['recentTicketIds'] ?? []);
        $orderIds = (array) ($payload['recentOrderIds'] ?? []);
        $invoiceIds = (array) ($payload['upcomingInvoiceIds'] ?? []);
        $contractIds = (array) ($payload['upcomingContractIds'] ?? []);

        $this->recentActivity = $activityIds
            ? ActivityLog::query()->whereIn('id', $activityIds)->with(['user'])->latest()->get()
            : collect();

        $this->recentTickets = $ticketIds
            ? SupportTicket::query()->whereIn('id', $ticketIds)->with(['creator', 'assignedTo'])->latest()->get()
            : collect();

        $this->recentOrders = $orderIds
            ? ServiceRequest::query()->whereIn('id', $orderIds)->latest()->get()
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
