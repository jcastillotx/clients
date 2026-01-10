<?php

namespace App\Http\Livewire\Contracts;

use App\Models\ActivityLog;
use App\Models\Contract;
use Livewire\Component;

class SignContract extends Component
{
    public Contract $contract;

    public string $signature = '';

    public string $signatureData = ''; // Base64 canvas signature

    public string $signatureMode = 'draw'; // 'draw' or 'type'

    public bool $agreeTerms = false;

    public function mount(Contract $contract): void
    {
        $this->authorizeClientAccess($contract);
        $this->contract = $contract;
    }

    public function setSignatureMode(string $mode): void
    {
        $this->signatureMode = $mode;
        // Clear the other signature type when switching
        if ($mode === 'draw') {
            $this->signature = '';
        } else {
            $this->signatureData = '';
        }
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

        if (! $this->contract->isPendingSignature() || $this->contract->isSigned()) {
            $this->addError('signature', 'This contract cannot be signed.');

            return;
        }

        // Validate based on signature mode
        if ($this->signatureMode === 'draw') {
            $this->validate([
                'signatureData' => ['required', 'string', 'regex:/^data:image\/(png|jpeg);base64,/'],
                'agreeTerms' => ['accepted'],
            ], [
                'signatureData.required' => 'Please draw your signature.',
                'signatureData.regex' => 'Invalid signature data.',
                'agreeTerms.accepted' => 'You must agree to the terms to sign.',
            ]);
            $signatureData = $this->signatureData;
        } else {
            $this->validate([
                'signature' => ['required', 'string', 'max:255'],
                'agreeTerms' => ['accepted'],
            ], [
                'agreeTerms.accepted' => 'You must agree to the terms to sign.',
            ]);
            $signatureData = $this->signature;
        }

        $this->contract->sign(
            auth()->user()->name,
            request()->ip() ?? '0.0.0.0',
            $signatureData
        );

        ActivityLog::log(
            "Signed contract: {$this->contract->title}",
            $this->contract,
            [
                'signed_by' => auth()->user()->name,
                'signature_mode' => $this->signatureMode,
            ],
            'signed',
            'contracts'
        );

        session()->flash('success', 'Contract signed successfully!');
        $this->contract->refresh();
    }

    public function clearSignature(): void
    {
        $this->signatureData = '';
    }

    public function render()
    {
        return view('livewire.contracts.sign-contract', [
            'contract' => $this->contract,
        ]);
    }
}
