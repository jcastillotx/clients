<?php

namespace App\Http\Livewire\AccountManagement;

use App\Models\Contract;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RenewalManager extends Component
{
    public int $days = 90;
    public ?int $editingId = null;
    public string $renewalStage = '';
    public string $renewalNotes = '';

    public function edit(int $contractId): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        $c = Contract::query()->findOrFail($contractId);
        $this->editingId = $c->id;
        $this->renewalStage = (string) ($c->meta['renewal_stage'] ?? '');
        $this->renewalNotes = (string) ($c->meta['renewal_notes'] ?? '');
    }

    public function save(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);
        abort_unless($this->editingId, 422);

        $c = Contract::query()->findOrFail($this->editingId);
        $meta = (array) ($c->meta ?? []);
        $meta['renewal_stage'] = trim($this->renewalStage) ?: null;
        $meta['renewal_notes'] = trim($this->renewalNotes) ?: null;
        $meta['renewal_updated_at'] = now()->toISOString();

        $c->update(['meta' => $meta]);
        session()->flash('success', 'Renewal notes saved.');
    }

    public function render()
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        $contracts = Contract::query()
            ->with('client')
            ->expiringSoon($this->days)
            ->orderBy('end_date')
            ->limit(200)
            ->get();

        return view('livewire.account-management.renewal-manager', [
            'contracts' => $contracts,
        ]);
    }
}

