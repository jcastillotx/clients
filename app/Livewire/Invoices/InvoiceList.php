<?php

namespace App\Livewire\Invoices;

use App\Models\Invoice;
use Livewire\Component;
use Livewire\WithPagination;

class InvoiceList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status']);
    }

    public function render()
    {
        $user = auth()->user();

        $query = Invoice::query()
            ->with('client')
            ->when($user->isClient(), function ($q) use ($user) {
                $q->where('client_id', $user->client_id);
            })
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('invoice_number', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->status, function ($q) {
                $q->where('status', $this->status);
            })
            ->orderBy($this->sortField, $this->sortDirection);

        $totals = [
            'unpaid' => Invoice::query()
                ->when($user->isClient(), fn ($q) => $q->where('client_id', $user->client_id))
                ->unpaid()
                ->sum('amount'),
            'overdue' => Invoice::query()
                ->when($user->isClient(), fn ($q) => $q->where('client_id', $user->client_id))
                ->overdue()
                ->sum('amount'),
        ];

        return view('livewire.invoices.invoice-list', [
            'invoices' => $query->paginate(10),
            'statuses' => config('client-portal.invoice_statuses'),
            'totals' => $totals,
        ]);
    }
}
