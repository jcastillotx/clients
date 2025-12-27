<?php

namespace App\Http\Livewire\Invoices;

use App\Models\Invoice;
use Livewire\Component;

class InvoiceShow extends Component
{
    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        $this->authorizeClientAccess($invoice);

        $this->invoice = $invoice->load([
            'client',
            'items',
            'payments',
        ]);
    }

    protected function authorizeClientAccess(Invoice $invoice): void
    {
        $user = auth()->user();

        if ($user->isClient() && $invoice->client_id !== $user->client_id) {
            abort(403);
        }
    }

    public function render()
    {
        return view('livewire.invoices.show');
    }
}
