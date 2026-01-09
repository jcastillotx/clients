<?php

namespace App\Http\Livewire\SupportTickets;

use App\Models\SupportTicket;
use Livewire\Component;
use Livewire\WithPagination;

class SupportTicketIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public string $status = '';

    public string $category = '';

    public string $billableFilter = '';

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

    public function updatingBillableFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $clientId = $user->isClient() ? $user->client_id : null;

        $query = SupportTicket::query()
            ->with(['client', 'creator', 'assignedTo', 'maintenancePlan'])
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
            ->when($this->search, fn ($q) => $q->where('subject', 'like', '%' . $this->search . '%'))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->category, fn ($q) => $q->where('category', $this->category))
            ->when($this->billableFilter !== '', function ($q) {
                if ($this->billableFilter === 'billable') {
                    return $q->where('is_billable', true);
                } elseif ($this->billableFilter === 'covered') {
                    return $q->where('is_billable', false);
                }
            })
            ->latest();

        // Status summary counts for the client
        $statusCounts = [];
        if ($clientId) {
            $statusCounts = SupportTicket::query()
                ->where('client_id', $clientId)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
        }

        return view('livewire.support-tickets.index', [
            'tickets' => $query->paginate(15),
            'statuses' => array_keys(config('client-portal.support_ticket_statuses', [])),
            'statusLabels' => config('client-portal.support_ticket_statuses', []),
            'categories' => config('client-portal.support_ticket_categories', []),
            'statusCounts' => $statusCounts,
        ]);
    }
}
