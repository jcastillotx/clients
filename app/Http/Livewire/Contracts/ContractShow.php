<?php

namespace App\Http\Livewire\Contracts;

use App\Models\Contract;
use Livewire\Component;

class ContractShow extends Component
{
    public Contract $contract;

    public function mount(Contract $contract): void
    {
        $this->authorizeClientAccess($contract);

        $this->contract = $contract->load('client');
    }

    protected function authorizeClientAccess(Contract $contract): void
    {
        $user = auth()->user();

        if ($user->isClient() && $contract->client_id !== $user->client_id) {
            abort(403);
        }
    }

    public function render()
    {
        return view('livewire.contracts.show');
    }
}

