<?php

namespace App\Http\Livewire\Admin\Invoices;

use App\Models\Invoice;
use App\Services\AI\InvoiceGeneratorAI;
use Livewire\Component;

class InvoiceAIAssistant extends Component
{
    public int $invoiceId;

    public bool $editable = false;

    public ?Invoice $invoice = null;

    /** @var array<string,mixed>|null */
    public ?array $generated = null;

    /** @var array<string,mixed>|null */
    public ?array $review = null;

    /** @var array<string,mixed>|null */
    public ?array $prediction = null;

    /** @var array<string,mixed>|null */
    public ?array $paymentPlan = null;

    public string $disputeText = '';

    /** @var array<string,mixed>|null */
    public ?array $dispute = null;

    public function mount(int $invoiceId, bool $editable = false): void
    {
        $this->invoiceId = $invoiceId;
        $this->editable = $editable;
        $this->loadInvoice();
    }

    public function loadInvoice(): void
    {
        $this->invoice = Invoice::query()->with(['client', 'items', 'request'])->findOrFail($this->invoiceId);

        // Staff can only access invoices for assigned clients
        $user = auth()->user();
        if ($user && $user->hasRole('staff') && ! $user->hasAnyRole(['super_admin', 'admin'])) {
            $allowed = $user->assignedClientIds();
            if (! in_array((int) $this->invoice->client_id, $allowed, true)) {
                abort(403, 'You do not have access to this invoice.');
            }
        }
    }

    public function generateFromRequest(InvoiceGeneratorAI $ai): void
    {
        $this->loadInvoice();
        if (! $this->invoice?->request) {
            session()->flash('error', 'This invoice is not linked to a request.');

            return;
        }

        $this->generated = $ai->autoGenerateInvoice($this->invoice->request, [
            'user_id' => auth()->id(),
        ]);
        session()->flash('success', 'AI generated draft line items. Review and apply if correct.');
    }

    public function applyGenerated(InvoiceGeneratorAI $ai): void
    {
        $this->loadInvoice();
        if (! $this->editable) {
            session()->flash('error', 'This invoice is not editable.');

            return;
        }
        if (! $this->generated || empty($this->generated['items'])) {
            session()->flash('error', 'No generated items to apply.');

            return;
        }

        $items = [];
        foreach ((array) $this->generated['items'] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $items[] = [
                'description' => (string) ($row['description'] ?? ''),
                'quantity' => (float) ($row['quantity'] ?? 0),
                'unit_price' => (float) ($row['unit_price'] ?? 0),
            ];
        }

        $notes = null;
        if (! empty($this->generated['notes_for_client'])) {
            $notes = (string) $this->generated['notes_for_client'];
        }

        $ai->applyItemsToInvoice(
            $this->invoice,
            $items,
            (float) ($this->generated['discount'] ?? 0),
            (float) ($this->generated['tax_rate'] ?? 0),
            $notes
        );

        $this->loadInvoice();
        session()->flash('success', 'Applied AI-generated items to the invoice.');
        $this->dispatch('invoice-ai-updated');
    }

    public function runReview(InvoiceGeneratorAI $ai): void
    {
        $this->loadInvoice();
        $this->review = $ai->reviewInvoice($this->invoice, [
            'user_id' => auth()->id(),
        ]);
        session()->flash('success', 'Invoice review complete.');
    }

    public function predictPayment(InvoiceGeneratorAI $ai): void
    {
        $this->loadInvoice();
        if (! $this->invoice?->client) {
            session()->flash('error', 'Missing client.');

            return;
        }
        $this->prediction = $ai->predictPayment($this->invoice, $this->invoice->client);
        session()->flash('success', 'Payment prediction generated.');
    }

    public function suggestPlan(InvoiceGeneratorAI $ai): void
    {
        $this->loadInvoice();
        if (! $this->invoice?->client) {
            session()->flash('error', 'Missing client.');

            return;
        }
        $this->paymentPlan = $ai->suggestPaymentPlan($this->invoice, $this->invoice->client, [
            'user_id' => auth()->id(),
        ]);
        session()->flash('success', 'Payment plan suggestion generated.');
    }

    public function draftDisputeResponse(InvoiceGeneratorAI $ai): void
    {
        $this->loadInvoice();
        if (! $this->invoice?->client) {
            session()->flash('error', 'Missing client.');

            return;
        }
        if (trim($this->disputeText) === '') {
            session()->flash('error', 'Enter the client’s dispute message first.');

            return;
        }

        $this->dispute = $ai->disputeResolution($this->invoice, $this->invoice->client, $this->disputeText, [
            'user_id' => auth()->id(),
        ]);
        session()->flash('success', 'Draft dispute response generated.');
    }

    public function render()
    {
        return view('livewire.admin.invoices.ai-assistant')->layout('layouts.admin');
    }
}
