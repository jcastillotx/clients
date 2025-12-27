<?php

namespace App\Http\Livewire\AccountManagement;

use App\Models\AccountHealth;
use App\Models\Client;
use App\Models\ClientHealthSnapshot;
use App\Models\SuccessMilestone;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AccountHealthDashboard extends Component
{
    public ?int $clientId = null;

    public string $riskFactorsJson = '[]';

    public string $opportunitiesJson = '[]';

    public string $milestoneName = '';

    public ?string $milestoneTargetDate = null;

    public function mount(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);
    }

    public function loadClient(): void
    {
        if (! $this->clientId) {
            return;
        }
        $health = AccountHealth::query()->firstOrNew(['client_id' => $this->clientId]);
        $this->riskFactorsJson = json_encode((array) ($health->risk_factors ?? []), JSON_PRETTY_PRINT);
        $this->opportunitiesJson = json_encode((array) ($health->opportunities ?? []), JSON_PRETTY_PRINT);
    }

    public function saveAccountHealth(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);
        abort_unless($this->clientId, 422);

        $risk = json_decode($this->riskFactorsJson ?: '[]', true);
        $opps = json_decode($this->opportunitiesJson ?: '[]', true);

        AccountHealth::updateOrCreate(
            ['client_id' => $this->clientId],
            [
                'risk_factors' => is_array($risk) ? $risk : [],
                'opportunities' => is_array($opps) ? $opps : [],
                'calculated_at' => now(),
            ]
        );

        session()->flash('success', 'Account health saved.');
    }

    public function addMilestone(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);
        abort_unless($this->clientId, 422);
        abort_unless(trim($this->milestoneName) !== '', 422);

        SuccessMilestone::create([
            'client_id' => $this->clientId,
            'milestone_name' => trim($this->milestoneName),
            'target_date' => $this->milestoneTargetDate ?: null,
            'status' => 'open',
            'celebration_sent' => false,
        ]);

        $this->reset(['milestoneName', 'milestoneTargetDate']);
        session()->flash('success', 'Milestone added.');
    }

    public function render()
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        $clients = Client::query()->orderBy('company_name')->get(['id', 'company_name']);
        $client = $this->clientId ? Client::query()->find($this->clientId) : null;

        $snapshot = $this->clientId
            ? ClientHealthSnapshot::query()->where('client_id', $this->clientId)->orderByDesc('computed_at')->first()
            : null;

        $health = $this->clientId ? AccountHealth::query()->where('client_id', $this->clientId)->first() : null;
        $milestones = $this->clientId ? SuccessMilestone::query()->where('client_id', $this->clientId)->orderByDesc('id')->limit(50)->get() : collect();

        return view('livewire.account-management.account-health-dashboard', compact('clients', 'client', 'snapshot', 'health', 'milestones'));
    }
}
