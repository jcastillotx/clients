<?php

namespace App\Http\Livewire\Proposals;

use App\Models\Proposal;
use App\Models\Request as ServiceRequest;
use App\Notifications\ProposalSentNotification;
use App\Services\Marketing\ProposalBuilderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class ProposalBuilder extends Component
{
    public ?int $proposalId = null;

    public ?int $requestId = null;

    public string $title = '';

    public string $templateId = '';

    public string $contentJson = '';

    public string $pricingJson = '';

    public function mount(?Proposal $proposal = null): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        if ($proposal) {
            $this->proposalId = $proposal->id;
            $this->requestId = $proposal->request_id;
            $this->title = $proposal->title;
            $this->templateId = (string) ($proposal->template_id ?? '');
            $this->contentJson = json_encode($proposal->content ?? [], JSON_PRETTY_PRINT);
            $this->pricingJson = json_encode($proposal->pricing_data ?? [], JSON_PRETTY_PRINT);
        }
    }

    public function generateFromRequest(ProposalBuilderService $svc): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        Validator::make(['requestId' => $this->requestId], [
            'requestId' => ['required', 'integer', 'exists:requests,id'],
        ])->validate();

        $req = ServiceRequest::query()->with('client')->findOrFail($this->requestId);
        $proposal = $svc->generateProposal($req, [], ['created_by' => $user->id]);

        $this->proposalId = $proposal->id;
        $this->title = $proposal->title;
        $this->templateId = (string) ($proposal->template_id ?? '');
        $this->contentJson = json_encode($proposal->content ?? [], JSON_PRETTY_PRINT);
        $this->pricingJson = json_encode($proposal->pricing_data ?? [], JSON_PRETTY_PRINT);

        session()->flash('success', 'Proposal draft generated from request.');
    }

    public function save(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        $content = json_decode($this->contentJson ?: '[]', true);
        $pricing = json_decode($this->pricingJson ?: '[]', true);

        Validator::make([
            'title' => $this->title,
            'content' => $content,
            'pricing' => $pricing,
        ], [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['array'],
            'pricing' => ['array'],
        ])->validate();

        if ($this->proposalId) {
            $p = Proposal::query()->findOrFail($this->proposalId);
            $p->update([
                'title' => $this->title,
                'template_id' => trim($this->templateId) ?: null,
                'content' => $content,
                'pricing_data' => $pricing,
            ]);
        } else {
            // Allow manual creation only if requestId is set.
            Validator::make(['requestId' => $this->requestId], [
                'requestId' => ['required', 'integer', 'exists:requests,id'],
            ])->validate();

            $req = ServiceRequest::query()->with('client')->findOrFail($this->requestId);
            $p = Proposal::create([
                'client_id' => $req->client_id,
                'request_id' => $req->id,
                'title' => $this->title,
                'proposal_number' => 'PROP-'.$req->client_id.'-'.now()->format('Ymd').'-'.strtoupper(\Illuminate\Support\Str::random(4)),
                'template_id' => trim($this->templateId) ?: null,
                'content' => $content,
                'pricing_data' => $pricing,
                'status' => 'draft',
                'valid_until' => now()->addDays(14)->toDateString(),
                'created_by' => $user->id,
            ]);
            $this->proposalId = $p->id;
        }

        session()->flash('success', 'Proposal saved.');
    }

    public function sendToClient(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);
        abort_unless($this->proposalId, 422);

        $p = Proposal::query()->with('client.users')->findOrFail($this->proposalId);
        $p->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $recipients = $p->client?->users ?? collect();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ProposalSentNotification($p));
        }

        session()->flash('success', 'Proposal sent to client.');
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        $requests = ServiceRequest::query()
            ->with('client')
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'client_id', 'title']);

        $proposal = $this->proposalId ? Proposal::query()->find($this->proposalId) : null;

        return view('livewire.proposals.proposal-builder', [
            'requests' => $requests,
            'proposal' => $proposal,
        ]);
    }
}
