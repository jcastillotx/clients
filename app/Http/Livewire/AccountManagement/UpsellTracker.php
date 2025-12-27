<?php

namespace App\Http\Livewire\AccountManagement;

use App\Models\AccountHealth;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class UpsellTracker extends Component
{
    public ?int $clientId = null;

    public string $opportunitiesJson = '[]';

    public function loadClient(): void
    {
        if (! $this->clientId) {
            return;
        }
        $row = AccountHealth::query()->firstOrNew(['client_id' => $this->clientId]);
        $this->opportunitiesJson = json_encode((array) ($row->opportunities ?? []), JSON_PRETTY_PRINT);
    }

    public function save(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);
        abort_unless($this->clientId, 422);

        $opps = json_decode($this->opportunitiesJson ?: '[]', true);
        if (! is_array($opps)) {
            $opps = [];
        }

        AccountHealth::updateOrCreate(
            ['client_id' => $this->clientId],
            ['opportunities' => $opps, 'calculated_at' => now()]
        );
        session()->flash('success', 'Opportunities saved.');
    }

    public function render()
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        $clients = Client::query()->orderBy('company_name')->get(['id', 'company_name']);
        $row = $this->clientId ? AccountHealth::query()->where('client_id', $this->clientId)->first() : null;

        return view('livewire.account-management.upsell-tracker', [
            'clients' => $clients,
            'row' => $row,
        ]);
    }
}
