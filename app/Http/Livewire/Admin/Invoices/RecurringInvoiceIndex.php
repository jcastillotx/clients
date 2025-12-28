<?php

namespace App\Http\Livewire\Admin\Invoices;

use App\Models\ActivityLog;
use App\Models\RecurringInvoice;
use Livewire\Component;
use Livewire\WithPagination;

class RecurringInvoiceIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $frequency = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'frequency' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFrequency(): void
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

    public function pause(int $id): void
    {
        $recurring = RecurringInvoice::findOrFail($id);
        $recurring->update(['status' => RecurringInvoice::STATUS_PAUSED]);

        ActivityLog::log(
            "Paused recurring invoice: {$recurring->name}",
            $recurring,
            ['previous_status' => 'active'],
            'updated',
            'recurring_invoices'
        );

        session()->flash('success', 'Recurring invoice paused.');
    }

    public function resume(int $id): void
    {
        $recurring = RecurringInvoice::findOrFail($id);

        // Recalculate next generate date if it's in the past
        if ($recurring->next_generate_date && $recurring->next_generate_date->isPast()) {
            $recurring->next_generate_date = $recurring->calculateNextGenerateDate(now());
        }

        $recurring->update(['status' => RecurringInvoice::STATUS_ACTIVE]);

        ActivityLog::log(
            "Resumed recurring invoice: {$recurring->name}",
            $recurring,
            ['previous_status' => 'paused'],
            'updated',
            'recurring_invoices'
        );

        session()->flash('success', 'Recurring invoice resumed.');
    }

    public function cancel(int $id): void
    {
        $recurring = RecurringInvoice::findOrFail($id);
        $recurring->update(['status' => RecurringInvoice::STATUS_CANCELLED]);

        ActivityLog::log(
            "Cancelled recurring invoice: {$recurring->name}",
            $recurring,
            [],
            'updated',
            'recurring_invoices'
        );

        session()->flash('success', 'Recurring invoice cancelled.');
    }

    public function delete(int $id): void
    {
        $recurring = RecurringInvoice::findOrFail($id);
        $name = $recurring->name;
        $recurring->delete();

        ActivityLog::log(
            "Deleted recurring invoice: {$name}",
            null,
            ['recurring_invoice_id' => $id],
            'deleted',
            'recurring_invoices'
        );

        session()->flash('success', 'Recurring invoice deleted.');
    }

    public function render()
    {
        $query = RecurringInvoice::query()
            ->with(['client'])
            ->withCount('invoices');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('company_name', 'like', "%{$this->search}%"));
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->frequency) {
            $query->where('frequency', $this->frequency);
        }

        $recurring = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.admin.invoices.recurring-index', [
            'recurringInvoices' => $recurring,
            'statusOptions' => RecurringInvoice::statusOptions(),
            'frequencyOptions' => RecurringInvoice::frequencyOptions(),
        ])->layout('layouts.admin', ['title' => 'Recurring Invoices']);
    }
}
