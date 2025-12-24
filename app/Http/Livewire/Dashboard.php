<?php

namespace App\Http\Livewire;

use App\Models\ActivityLog;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Request as ServiceRequest;
use Illuminate\Support\Collection;
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

    public function mount(): void
    {
        $user = auth()->user();

        $clientId = $user?->client_id;
        if (!$clientId) {
            $this->recentActivity = collect();
            $this->upcomingInvoices = collect();
            $this->upcomingContracts = collect();
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
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}

