<?php

namespace App\Http\Livewire\SupportTickets;

use App\Models\ActivityLog;
use App\Models\SupportTicket;
use App\Models\SupportTicketComment;
use Illuminate\Support\Collection;
use Livewire\Component;

class SupportTicketShow extends Component
{
    public SupportTicket $ticket;

    public string $newComment = '';

    /** @var \Illuminate\Support\Collection<int, \App\Models\ActivityLog> */
    public Collection $statusHistory;

    public function mount(SupportTicket $ticket): void
    {
        $this->authorizeClientAccess($ticket);

        $this->ticket = $ticket->load([
            'client',
            'creator',
            'assignedTo',
            'maintenancePlan',
            'publicComments.user',
        ]);

        $this->loadStatusHistory();
    }

    protected function loadStatusHistory(): void
    {
        $this->statusHistory = ActivityLog::query()
            ->where('subject_type', SupportTicket::class)
            ->where('subject_id', $this->ticket->id)
            ->whereNotNull('event')
            ->latest()
            ->take(20)
            ->get();
    }

    protected function authorizeClientAccess(SupportTicket $ticket): void
    {
        $user = auth()->user();

        if ($user->isClient() && $ticket->client_id !== $user->client_id) {
            abort(403);
        }
    }

    public function addComment(): void
    {
        $this->validate([
            'newComment' => ['required', 'string', 'min:1'],
        ]);

        SupportTicketComment::create([
            'support_ticket_id' => $this->ticket->id,
            'user_id' => auth()->id(),
            'comment' => $this->newComment,
            'is_internal' => false,
        ]);

        $this->newComment = '';
        $this->ticket->load('publicComments.user');

        session()->flash('success', 'Comment added successfully.');
    }

    public function render()
    {
        return view('livewire.support-tickets.show');
    }
}
