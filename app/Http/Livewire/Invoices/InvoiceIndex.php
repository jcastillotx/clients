<?php

namespace App\Http\Livewire\Invoices;

use App\Models\Invoice;
use Livewire\Component;
use Livewire\WithPagination;

class InvoiceIndex extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    /**
     * all|unpaid|paid|overdue
     */
    public string $status = 'all';

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $query = Invoice::query()
            ->with('client')
            ->when($user->isClient(), fn ($q) => $q->where('client_id', $user->client_id))
            ->when($this->status === 'paid', fn ($q) => $q->where('status', 'paid'))
            ->when($this->status === 'overdue', fn ($q) => $q->where('status', 'overdue'))
            ->when($this->status === 'unpaid', fn ($q) => $q->whereIn('status', ['sent', 'overdue']))
            ->orderByDesc('created_at');

        return view('livewire.invoices.index', [
            'invoices' => $query->paginate(15),
        ]);
    }
}

