<?php

namespace App\Http\Livewire\Admin\Invoices;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Request as ServiceRequest;
use App\Services\NotificationService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class InvoiceEdit extends Component
{
    public Invoice $invoice;

    public bool $editable = false;

    public bool $autoNumber = false; // locked on edit
    public string $invoice_number = '';
    public ?int $client_id = null;
    public ?string $issue_date = null;
    public ?string $due_date = null;
    public string $status = 'draft';
    public string $template = 'classic';

    public ?int $request_id = null;
    public ?int $contract_id = null;

    /** @var array<int, array{id?:int|null, description:string, quantity:float|string, unit_price:float|string, total:float}> */
    public array $items = [];

    /** @var array<int, int> */
    public array $removedItemIds = [];

    public string $tax_rate = '';
    public string $discount = '';
    public string $notes = '';
    public string $terms = '';

    // Manual payment
    public bool $showPaymentModal = false;
    public string $payAmount = '';
    public string $payMethod = 'check';
    public string $payTransactionId = '';
    public ?string $payProcessedAt = null;
    public bool $paySendReceipt = true;

    public function mount(Invoice $invoice): void
    {
        $this->invoice = $invoice->load(['client', 'items', 'payments']);

        $this->editable = in_array($this->invoice->status, ['draft', 'sent'], true);

        $this->invoice_number = $this->invoice->invoice_number;
        $this->client_id = $this->invoice->client_id;
        $this->issue_date = $this->invoice->issue_date?->format('Y-m-d');
        $this->due_date = $this->invoice->due_date?->format('Y-m-d');
        $this->status = $this->invoice->status;
        $this->template = $this->invoice->template ?: 'classic';
        $this->request_id = $this->invoice->request_id;
        $this->contract_id = $this->invoice->contract_id;

        $this->tax_rate = (string) ($this->invoice->tax_rate ?? 0);
        $this->discount = (string) ($this->invoice->discount ?? 0);
        $this->notes = (string) ($this->invoice->notes ?? '');
        $this->terms = (string) ($this->invoice->terms ?? '');

        $this->items = $this->invoice->items->map(fn (InvoiceItem $it) => [
            'id' => $it->id,
            'description' => $it->description,
            'quantity' => (float) $it->quantity,
            'unit_price' => (float) $it->unit_price,
            'total' => (float) $it->total,
        ])->toArray();

        if (empty($this->items)) {
            $this->items = [['description' => '', 'quantity' => 1, 'unit_price' => 0, 'total' => 0]];
        }
    }

    protected function rules(): array
    {
        $templates = array_keys(config('client-portal.invoice.templates', ['classic' => 'Classic']));

        return [
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'template' => ['required', Rule::in($templates)],
            'request_id' => ['nullable', 'integer', Rule::exists('requests', 'id')],
            'contract_id' => ['nullable', 'integer', Rule::exists('contracts', 'id')],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'terms' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'items.')) {
            $this->recalculate();
        }
    }

    public function addItem(): void
    {
        $this->items[] = ['description' => '', 'quantity' => 1, 'unit_price' => 0, 'total' => 0];
    }

    public function removeItem(int $index): void
    {
        $id = $this->items[$index]['id'] ?? null;
        if ($id) {
            $this->removedItemIds[] = (int) $id;
        }
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        if (empty($this->items)) {
            $this->items = [['description' => '', 'quantity' => 1, 'unit_price' => 0, 'total' => 0]];
        }
        $this->recalculate();
    }

    public function recalculate(): void
    {
        foreach ($this->items as $i => $row) {
            $qty = (float) ($row['quantity'] ?? 0);
            $unit = (float) ($row['unit_price'] ?? 0);
            $this->items[$i]['total'] = round($qty * $unit, 2);
        }
    }

    public function save(): void
    {
        if (!$this->editable) {
            session()->flash('error', 'This invoice can no longer be edited.');
            return;
        }

        $data = $this->validate();

        // Validate request/contract belong to client
        if ($data['request_id']) {
            $req = ServiceRequest::query()->where('client_id', $this->invoice->client_id)->find($data['request_id']);
            if (!$req) {
                throw \Illuminate\Validation\ValidationException::withMessages(['request_id' => 'Selected request does not belong to this client.']);
            }
        }
        if ($data['contract_id']) {
            $contract = Contract::query()->where('client_id', $this->invoice->client_id)->find($data['contract_id']);
            if (!$contract) {
                throw \Illuminate\Validation\ValidationException::withMessages(['contract_id' => 'Selected contract does not belong to this client.']);
            }
        }

        $this->invoice->update([
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'],
            'tax_rate' => (float) ($data['tax_rate'] === '' ? 0 : $data['tax_rate']),
            'discount' => (float) ($data['discount'] === '' ? 0 : $data['discount']),
            'notes' => $data['notes'] ?: null,
            'terms' => $data['terms'] ?: null,
            'template' => $data['template'],
            'request_id' => $data['request_id'],
            'contract_id' => $data['contract_id'],
        ]);

        // Delete removed items
        if (!empty($this->removedItemIds)) {
            InvoiceItem::query()->where('invoice_id', $this->invoice->id)->whereIn('id', $this->removedItemIds)->delete();
            $this->removedItemIds = [];
        }

        // Upsert items
        foreach (array_values($data['items']) as $idx => $row) {
            $payload = [
                'description' => $row['description'],
                'quantity' => (float) $row['quantity'],
                'unit_price' => (float) $row['unit_price'],
                'total' => (float) ((float) $row['quantity'] * (float) $row['unit_price']),
                'sort_order' => $idx,
            ];

            $id = $row['id'] ?? null;
            if ($id) {
                InvoiceItem::query()->where('invoice_id', $this->invoice->id)->where('id', $id)->update($payload);
            } else {
                InvoiceItem::create($payload + ['invoice_id' => $this->invoice->id]);
            }
        }

        $this->invoice->refresh()->load('items');
        $this->invoice->calculateTotals();

        ActivityLog::log(
            "Admin updated invoice: {$this->invoice->invoice_number}",
            $this->invoice,
            null,
            'updated',
            'invoices'
        );

        session()->flash('success', 'Invoice updated.');
    }

    public function voidInvoice(): void
    {
        if (!in_array($this->invoice->status, ['draft', 'sent', 'overdue'], true)) {
            session()->flash('error', 'Only draft/sent/overdue invoices can be voided.');
            return;
        }

        $this->invoice->update([
            'status' => 'cancelled',
            'paid_at' => null,
        ]);
        $this->invoice->refresh();
        $this->editable = false;

        ActivityLog::log(
            "Voided invoice: {$this->invoice->invoice_number}",
            $this->invoice,
            null,
            'cancelled',
            'invoices'
        );

        session()->flash('success', 'Invoice voided.');
    }

    public function sendOrResend(NotificationService $notifications): void
    {
        if (!in_array($this->invoice->status, ['draft', 'sent', 'overdue'], true)) {
            session()->flash('error', 'Only draft/sent/overdue invoices can be emailed.');
            return;
        }

        if ($this->invoice->status === 'draft') {
            $this->invoice->markAsSent();
        }

        $notifications->sendInvoiceNotification($this->invoice->fresh(['client']), 'created');
        session()->flash('success', 'Invoice email queued.');
    }

    public function openPaymentModal(): void
    {
        $this->payAmount = '';
        $this->payMethod = 'check';
        $this->payTransactionId = '';
        $this->payProcessedAt = null;
        $this->paySendReceipt = true;
        $this->showPaymentModal = true;
    }

    public function savePayment(NotificationService $notifications): void
    {
        $this->validate([
            'payAmount' => ['required', 'numeric', 'min:0.01'],
            'payMethod' => ['required', 'string', 'max:50'],
            'payTransactionId' => ['nullable', 'string', 'max:255'],
            'payProcessedAt' => ['nullable', 'string'],
            'paySendReceipt' => ['boolean'],
        ]);

        $processedAt = $this->payProcessedAt ? \Carbon\Carbon::parse($this->payProcessedAt) : now();

        $payment = Payment::create([
            'invoice_id' => $this->invoice->id,
            'client_id' => $this->invoice->client_id,
            'amount' => (float) $this->payAmount,
            'payment_method' => $this->payMethod,
            'transaction_id' => $this->payTransactionId ?: null,
            'status' => 'succeeded',
            'processed_at' => $processedAt,
            'metadata' => ['recorded_by' => auth()->id(), 'source' => 'manual'],
        ]);

        $this->invoice->refresh();
        if ($this->invoice->balance_due <= 0) {
            $this->invoice->markAsPaid();
            $this->editable = false;
        }

        if ($this->paySendReceipt) {
            $notifications->sendPaymentNotification($payment);
        }

        $this->showPaymentModal = false;
        session()->flash('success', 'Payment recorded.');
    }

    public function render()
    {
        $requests = ServiceRequest::query()->where('client_id', $this->invoice->client_id)->latest()->limit(50)->get(['id', 'title', 'status']);
        $contracts = Contract::query()->where('client_id', $this->invoice->client_id)->latest()->limit(50)->get(['id', 'title', 'status']);

        return view('livewire.admin.invoices.edit', [
            'invoice' => $this->invoice->fresh(['client', 'items', 'payments']),
            'invoiceStatuses' => config('client-portal.invoice_statuses', []),
            'templates' => config('client-portal.invoice.templates', []),
            'requests' => $requests,
            'contracts' => $contracts,
        ])->layout('layouts.admin', ['title' => 'Edit ' . $this->invoice->invoice_number]);
    }
}

