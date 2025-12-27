<?php

namespace App\Http\Livewire\Proposals;

use App\Models\Proposal;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProposalAnalytics extends Component
{
    public ?int $proposalId = null;

    public function mount(?Proposal $proposal = null): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);
        $this->proposalId = $proposal?->id;
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        $proposal = $this->proposalId ? Proposal::query()->with(['views', 'selections'])->find($this->proposalId) : null;

        return view('livewire.proposals.proposal-analytics', [
            'proposal' => $proposal,
        ])->layout('layouts.admin', ['title' => 'Proposal Analytics']);
    }
}

