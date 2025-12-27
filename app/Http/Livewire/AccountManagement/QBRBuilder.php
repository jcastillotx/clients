<?php

namespace App\Http\Livewire\AccountManagement;

use App\Models\Client;
use App\Models\QbrMeeting;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class QBRBuilder extends Component
{
    public ?int $clientId = null;

    public ?string $scheduledDate = null;

    public string $presentationUrl = '';

    public string $notes = '';

    public string $actionItemsJson = '[]';

    public ?string $nextQbrDate = null;

    public function mount(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);
    }

    public function create(): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);
        abort_unless($this->clientId, 422);

        $items = json_decode($this->actionItemsJson ?: '[]', true);
        if (! is_array($items)) {
            $items = [];
        }

        QbrMeeting::create([
            'client_id' => $this->clientId,
            'scheduled_date' => $this->scheduledDate ?: null,
            'presentation_url' => trim($this->presentationUrl) ?: null,
            'notes' => trim($this->notes) ?: null,
            'action_items' => $items,
            'next_qbr_date' => $this->nextQbrDate ?: null,
        ]);

        $this->reset(['scheduledDate', 'presentationUrl', 'notes', 'actionItemsJson', 'nextQbrDate']);
        $this->actionItemsJson = '[]';
        session()->flash('success', 'QBR saved.');
    }

    public function render()
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        $clients = Client::query()->orderBy('company_name')->get(['id', 'company_name']);
        $qbrs = $this->clientId
            ? QbrMeeting::query()->where('client_id', $this->clientId)->orderByDesc('id')->limit(50)->get()
            : collect();

        return view('livewire.account-management.qbr-builder', compact('clients', 'qbrs'));
    }
}
