<?php

namespace App\Http\Livewire\Admin\SupportTickets;

use App\Models\Client;
use App\Models\SupportTicket;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class SupportTicketManagement extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public string $status = '';

    public string $category = '';

    public string $billableFilter = '';

    public ?int $clientId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function updatingClientId(): void
    {
        $this->resetPage();
    }

    public function updatingBillableFilter(): void
    {
        $this->resetPage();
    }

    public function updateStatus(int $ticketId, string $newStatus): void
    {
        $ticket = SupportTicket::findOrFail($ticketId);
        $ticket->update(['status' => $newStatus]);

        if ($newStatus === 'resolved') {
            $ticket->update(['resolved_at' => now()]);
        } elseif ($newStatus === 'closed') {
            $ticket->update(['closed_at' => now()]);
        }

        session()->flash('success', 'Ticket status updated.');
    }

    public function assignTicket(int $ticketId, ?int $userId): void
    {
        $ticket = SupportTicket::findOrFail($ticketId);
        $ticket->update([
            'assigned_to' => $userId,
            'first_response_at' => $ticket->first_response_at ?? now(),
        ]);

        session()->flash('success', 'Ticket assigned successfully.');
    }

    public function render()
    {
        $query = SupportTicket::query()
            ->with(['client', 'creator', 'assignedTo', 'maintenancePlan'])
            ->when($this->search, fn ($q) => $q->where('subject', 'like', '%' . $this->search . '%')
                ->orWhere('ticket_number', 'like', '%' . $this->search . '%'))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->category, fn ($q) => $q->where('category', $this->category))
            ->when($this->clientId, fn ($q) => $q->where('client_id', $this->clientId))
            ->when($this->billableFilter !== '', function ($q) {
                if ($this->billableFilter === 'billable') {
                    return $q->where('is_billable', true);
                } elseif ($this->billableFilter === 'covered') {
                    return $q->where('is_billable', false);
                }
            })
            ->latest();

        // Status counts for summary
        $statusCounts = SupportTicket::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return view('livewire.admin.support-tickets.management', [
            'tickets' => $query->paginate(20),
            'statuses' => array_keys(config('client-portal.support_ticket_statuses', [])),
            'statusLabels' => config('client-portal.support_ticket_statuses', []),
            'categories' => config('client-portal.support_ticket_categories', []),
            'statusCounts' => $statusCounts,
            'clients' => Client::orderBy('company_name')->get(),
            'staff' => User::role(['admin', 'super_admin', 'staff'])->orderBy('name')->get(),
        ]);
    }
}
