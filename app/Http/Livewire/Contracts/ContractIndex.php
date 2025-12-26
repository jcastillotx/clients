<?php

namespace App\Http\Livewire\Contracts;

use App\Models\Contract;
use Livewire\Component;
use Livewire\WithPagination;

class ContractIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $status = 'all';

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $query = Contract::query()
            ->when($user->isClient(), fn ($q) => $q->where('client_id', $user->client_id))
            ->when($this->status !== 'all' && $this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('created_at');

        return view('livewire.contracts.index', [
            'contracts' => $query->paginate(15),
        ]);
    }
}

