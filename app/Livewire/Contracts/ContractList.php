<?php

namespace App\Livewire\Contracts;

use App\Models\Contract;
use Livewire\Component;
use Livewire\WithPagination;

class ContractList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status']);
    }

    public function render()
    {
        $user = auth()->user();

        $query = Contract::query()
            ->with('client')
            ->when($user->isClient(), function ($q) use ($user) {
                $q->where('client_id', $user->client_id);
            })
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('title', 'like', '%' . $this->search . '%')
                        ->orWhere('contract_number', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, function ($q) {
                $q->where('status', $this->status);
            })
            ->orderBy('created_at', 'desc');

        return view('livewire.contracts.contract-list', [
            'contracts' => $query->paginate(10),
            'statuses' => config('client-portal.contract_statuses'),
        ]);
    }
}
