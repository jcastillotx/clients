<?php

namespace App\Http\Livewire\Admin\Contracts;

use App\Models\Client;
use App\Models\Contract;
use Livewire\Component;
use Livewire\WithPagination;

class ContractManagement extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public string $status = '';

    public ?int $clientId = null;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'clientId' => ['except' => null],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];

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

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function deleteContract(int $id): void
    {
        $contract = Contract::query()->findOrFail($id);
        $contract->delete();

        session()->flash('success', 'Contract deleted successfully.');
    }

    public function render()
    {
        $user = auth()->user();
        $staffClientIds = [];
        if ($user && $user->hasRole('staff') && ! $user->hasAnyRole(['super_admin', 'admin'])) {
            $staffClientIds = $user->assignedClientIds();
        }

        $query = Contract::query()
            ->with('client')
            ->when($this->search !== '', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('contract_number', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->clientId, fn ($q) => $q->where('client_id', $this->clientId))
            ->when(! empty($staffClientIds), fn ($q) => $q->whereIn('client_id', $staffClientIds))
            ->orderBy($this->sortField, $this->sortDirection);

        $clients = Client::query()
            ->when(! empty($staffClientIds), fn ($q) => $q->whereIn('id', $staffClientIds))
            ->orderBy('company_name')
            ->get(['id', 'company_name']);

        $statusCounts = Contract::query()
            ->when(! empty($staffClientIds), fn ($q) => $q->whereIn('client_id', $staffClientIds))
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return view('livewire.admin.contracts.index', [
            'contracts' => $query->paginate(15),
            'clients' => $clients,
            'statuses' => config('client-portal.contract_statuses', [
                'draft' => 'Draft',
                'pending_signature' => 'Pending Signature',
                'active' => 'Active',
                'expired' => 'Expired',
                'terminated' => 'Terminated',
            ]),
            'statusCounts' => $statusCounts,
        ])->layout('layouts.admin', ['title' => 'Contract Management']);
    }
}
