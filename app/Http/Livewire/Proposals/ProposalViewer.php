<?php

namespace App\Http\Livewire\Proposals;

use App\Models\Proposal;
use App\Models\ProposalSelection;
use App\Models\ProposalView;
use App\Services\Marketing\OnboardingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProposalViewer extends Component
{
    public Proposal $proposal;

    public string $selectedTier = 'better';
    public array $selectedAddons = [];

    public function mount(Proposal $proposal): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);
        abort_unless((int) $proposal->client_id === (int) $user->client_id, 403);

        $this->proposal = $proposal;

        // Track view
        ProposalView::create([
            'proposal_id' => $proposal->id,
            'viewed_at' => now(),
            'ip_address' => request()->ip(),
        ]);

        if ($proposal->status === 'sent') {
            $proposal->update(['status' => 'viewed']);
        }
    }

    public function totalAmount(): float
    {
        $pricing = (array) ($this->proposal->pricing_data ?? []);
        $tiers = (array) ($pricing['tiers'] ?? []);
        $tierAmount = (float) (($tiers[$this->selectedTier]['amount'] ?? null) ?: 0);

        // Add-ons amounts may be null; treat as 0 unless explicitly set
        $addons = (array) ($pricing['addons'] ?? []);
        $addonTotal = 0.0;
        foreach ($addons as $a) {
            if (!is_array($a)) continue;
            $key = (string) ($a['key'] ?? '');
            if ($key === '' || !in_array($key, $this->selectedAddons, true)) continue;
            $addonTotal += (float) (($a['amount'] ?? null) ?: 0);
        }

        return $tierAmount + $addonTotal;
    }

    public function saveSelection(): void
    {
        ProposalSelection::create([
            'proposal_id' => $this->proposal->id,
            'selected_tier' => $this->selectedTier ?: null,
            'selected_addons' => $this->selectedAddons ?: null,
            'total_amount' => $this->totalAmount(),
        ]);

        session()->flash('success', 'Selection saved.');
    }

    public function accept(OnboardingService $onboarding): mixed
    {
        $user = Auth::user();
        abort_unless($user && $user->isClient(), 403);
        abort_unless($this->proposal->status !== 'accepted', 422);

        $this->saveSelection();

        $this->proposal->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        // Kick off onboarding (idempotent)
        $onboarding->createOnboardingWorkflow($user->client);

        session()->flash('success', 'Proposal accepted. Next: onboarding + contract steps.');
        return redirect()->route('client.onboarding');
    }

    public function render()
    {
        $pricing = (array) ($this->proposal->pricing_data ?? []);
        $content = (array) ($this->proposal->content ?? []);

        return view('livewire.proposals.proposal-viewer', [
            'content' => $content,
            'pricing' => $pricing,
            'total' => $this->totalAmount(),
        ]);
    }
}

