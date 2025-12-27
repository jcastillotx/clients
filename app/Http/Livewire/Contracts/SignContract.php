<?php

namespace App\Http\Livewire\Contracts;

use App\Models\ActivityLog;
use App\Models\Contract;
use Livewire\Component;

class SignContract extends Component
{
    public Contract $contract;

    public string $signature = '';
    public bool $agreeTerms = false;

    public function mount(Contract $contract): void
    {
        $this->authorizeClientAccess($contract);
        $this->contract = $contract;
    }

    protected function authorizeClientAccess(Contract $contract): void
    {
        $user = auth()->user();
        if ($user->isClient() && $contract->client_id !== $user->client_id) {
            abort(403);
        }
    }

    public function sign(): void
    {
        $this->authorizeClientAccess($this->contract);

        if (!$this->contract->isPendingSignature() || $this->contract->isSigned()) {
            $this->addError('signature', 'This contract cannot be signed.');
            return;
        }

        $validated = $this->validate([
            'signature' => ['required', 'string', 'max:255'],
            'agreeTerms' => ['accepted'],
        ], [
            'agreeTerms.accepted' => 'You must agree to the terms to sign.',
        ]);

        $this->contract->sign(
            auth()->user()->name,
            request()->ip() ?? '0.0.0.0',
            $validated['signature']
        );

        ActivityLog::log(
            "Signed contract: {$this->contract->title}",
            $this->contract,
            ['signed_by' => auth()->user()->name],
            'signed',
            'contracts'
        );

        session()->flash('success', 'Contract signed successfully!');
        $this->contract->refresh();
    }

    public function render()
    {
        return view('livewire.contracts.sign-contract', [
            'contract' => $this->contract,
        ]);
    }
}

