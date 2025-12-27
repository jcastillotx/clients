<?php

namespace App\Http\Livewire\Admin\Invoices;

use App\Models\Invoice;
use App\Services\AI\InvoiceGeneratorAI;
use Livewire\Component;

class PricingOptimizer extends Component
{
    public int $invoiceId;
    public bool $editable = false;

    public ?Invoice $invoice = null;

    /** @var array<string,mixed>|null */
    public ?array $pricing = null;

    public function mount(int $invoiceId, bool $editable = false): void
    {
        $this->invoiceId = $invoiceId;
        $this->editable = $editable;
        $this->loadInvoice();
    }

    public function loadInvoice(): void
    {
        $this->invoice = Invoice::query()->with(['client', 'items'])->findOrFail($this->invoiceId);

        // Staff can only access invoices for assigned clients
        $user = auth()->user();
        if ($user && $user->hasRole('staff') && !$user->hasAnyRole(['super_admin', 'admin'])) {
            $allowed = $user->assignedClientIds();
            if (!in_array((int) $this->invoice->client_id, $allowed, true)) {
                abort(403, 'You do not have access to this invoice.');
            }
        }
    }

    public function optimize(InvoiceGeneratorAI $ai): void
    {
        $this->loadInvoice();
        if (!$this->invoice?->client) {
            session()->flash('error', 'Missing client.');
            return;
        }

        $this->pricing = $ai->optimizePricing($this->invoice, $this->invoice->client, [
            'user_id' => auth()->id(),
        ]);

        session()->flash('success', 'Pricing optimization complete.');
    }

    public function applySuggestedDiscount(): void
    {
        $this->loadInvoice();
        if (!$this->editable) {
            session()->flash('error', 'This invoice is not editable.');
            return;
        }
        $disc = (float) (($this->pricing['suggested_discount']['amount'] ?? 0));
        if ($disc <= 0) {
            session()->flash('error', 'No discount suggested.');
            return;
        }

        $this->invoice->update(['discount' => $disc]);
        if (method_exists($this->invoice, 'calculateTotals')) {
            $this->invoice->refresh()->load('items');
            $this->invoice->calculateTotals();
        }
        $this->loadInvoice();
        session()->flash('success', 'Applied suggested discount to invoice.');
        $this->dispatch('invoice-ai-updated');
    }

    public function render()
    {
        return view('livewire.admin.invoices.pricing-optimizer');
    }
}

