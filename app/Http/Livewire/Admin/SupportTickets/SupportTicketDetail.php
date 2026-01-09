<?php

namespace App\Http\Livewire\Admin\SupportTickets;

use App\Models\ActivityLog;
use App\Models\SupportTicket;
use App\Models\SupportTicketComment;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Component;

class SupportTicketDetail extends Component
{
    public SupportTicket $ticket;

    public string $newComment = '';

    public bool $isInternal = false;

    public ?int $assignedTo = null;

    public ?float $actualHours = null;

    /** @var \Illuminate\Support\Collection<int, \App\Models\ActivityLog> */
    public Collection $statusHistory;

    protected $rules = [
        'assignedTo' => ['nullable', 'exists:users,id'],
        'actualHours' => ['nullable', 'numeric', 'min:0'],
    ];

    public function mount(SupportTicket $ticket): void
    {
        $this->ticket = $ticket->load([
            'client',
            'creator',
            'assignedTo',
            'maintenancePlan',
            'comments.user',
            'invoice',
        ]);

        $this->assignedTo = $ticket->assigned_to;
        $this->actualHours = $ticket->actual_hours;
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

    public function updateStatus(string $newStatus): void
    {
        $this->ticket->update(['status' => $newStatus]);

        if ($newStatus === 'resolved') {
            $this->ticket->update(['resolved_at' => now()]);
        } elseif ($newStatus === 'closed') {
            $this->ticket->update(['closed_at' => now()]);
        } elseif ($newStatus === 'in_progress' && ! $this->ticket->first_response_at) {
            $this->ticket->update(['first_response_at' => now()]);
        }

        $this->ticket->refresh();
        $this->loadStatusHistory();

        session()->flash('success', 'Status updated successfully.');
    }

    public function updateAssignment(): void
    {
        $this->validate(['assignedTo' => ['nullable', 'exists:users,id']]);

        $this->ticket->update([
            'assigned_to' => $this->assignedTo,
            'first_response_at' => $this->ticket->first_response_at ?? now(),
        ]);

        $this->ticket->refresh();
        $this->loadStatusHistory();

        session()->flash('success', 'Assignment updated successfully.');
    }

    public function updateHours(): void
    {
        $this->validate(['actualHours' => ['nullable', 'numeric', 'min:0']]);

        $this->ticket->update(['actual_hours' => $this->actualHours]);
        $this->ticket->refresh();

        session()->flash('success', 'Hours updated successfully.');
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
            'is_internal' => $this->isInternal,
        ]);

        $this->newComment = '';
        $this->isInternal = false;
        $this->ticket->load('comments.user');

        session()->flash('success', 'Comment added successfully.');
    }

    public function render()
    {
        return view('livewire.admin.support-tickets.detail', [
            'statuses' => config('client-portal.support_ticket_statuses', []),
            'staff' => User::role(['admin', 'super_admin', 'staff'])->orderBy('name')->get(),
        ]);
    }
}
