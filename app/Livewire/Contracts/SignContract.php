<?php

namespace App\Livewire\Contracts;

use App\Models\Contract;
use App\Models\ActivityLog;
use Livewire\Component;

class SignContract extends Component
{
    public Contract $contract;
    public string $signature = '';
    public bool $agreeTerms = false;
    public bool $showSignaturePad = false;

    protected $rules = [
        'signature' => 'required|string',
        'agreeTerms' => 'required|accepted',
    ];

    protected $messages = [
        'signature.required' => 'Please provide your signature.',
        'agreeTerms.accepted' => 'You must agree to the terms to sign this contract.',
    ];

    public function mount(Contract $contract): void
    {
        $this->contract = $contract;
    }

    public function toggleSignaturePad(): void
    {
        $this->showSignaturePad = !$this->showSignaturePad;
    }

    public function clearSignature(): void
    {
        $this->signature = '';
    }

    public function sign(): void
    {
        $this->validate();

        $user = auth()->user();

        $this->contract->sign(
            $user->name,
            request()->ip(),
            $this->signature
        );

        ActivityLog::log(
            "Signed contract: {$this->contract->title}",
            $this->contract,
            ['signed_by' => $user->name],
            'signed',
            'contracts'
        );

        session()->flash('success', 'Contract signed successfully!');

        $this->redirect(route('contracts.show', $this->contract));
    }

    public function render()
    {
        return view('livewire.contracts.sign-contract');
    }
}
