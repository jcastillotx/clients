<?php

namespace App\Http\Livewire\Admin\MaintenancePlans;

use App\Models\Client;
use App\Models\MaintenancePlan;
use Livewire\Component;
use Livewire\WithPagination;

class MaintenancePlanIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public string $status = '';

    public ?int $clientId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingClientId(): void
    {
        $this->resetPage();
    }

    public function delete(int $planId): void
    {
        $plan = MaintenancePlan::findOrFail($planId);
        $plan->delete();

        session()->flash('success', 'Maintenance plan deleted.');
    }

    public function render()
    {
        $query = MaintenancePlan::query()
            ->with(['client', 'supportTickets'])
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->clientId, fn ($q) => $q->where('client_id', $this->clientId))
            ->latest();

        // Status counts
        $statusCounts = MaintenancePlan::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return view('livewire.admin.maintenance-plans.index', [
            'plans' => $query->paginate(20),
            'statuses' => config('client-portal.maintenance_plan_statuses', []),
            'statusCounts' => $statusCounts,
            'clients' => Client::orderBy('company_name')->get(),
        ]);
    }
}
