<?php

namespace App\Http\Livewire\Admin\Invoices;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Request as ServiceRequest;
use App\Services\NotificationService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class InvoiceCreate extends Component
{
    public ?int $client_id = null;

    public bool $autoNumber = true;

    public string $invoice_number = '';

    public ?string $issue_date = null; // YYYY-MM-DD

    public ?string $due_date = null;   // YYYY-MM-DD

    public ?int $request_id = null;

    public ?int $contract_id = null;

    public string $template = 'classic';

    /** @var array<int, array{description:string, feature_key:string|null, quantity:float|string, unit_price:float|string, total:float}> */
    public array $items = [];

    public string $tax_rate = '';

    public string $discount = '';

    public string $notes = '';

    public string $terms = '';

    public function mount(): void
    {
        $this->issue_date = now()->toDateString();
        $this->due_date = now()->addDays(30)->toDateString();
        $this->tax_rate = (string) config('client-portal.invoice.tax_rate', 0);
        $this->items = [
            ['description' => '', 'feature_key' => null, 'quantity' => 1, 'unit_price' => 0, 'total' => 0],
        ];
    }

    protected function rules(): array
    {
        $templates = array_keys(config('client-portal.invoice.templates', ['classic' => 'Classic']));
        $features = array_keys((array) config('features.available', []));

        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')],
            'autoNumber' => ['boolean'],
            'invoice_number' => [$this->autoNumber ? 'nullable' : 'required', 'string', 'max:255', Rule::unique('invoices', 'invoice_number')],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'request_id' => ['nullable', 'integer', Rule::exists('requests', 'id')],
            'contract_id' => ['nullable', 'integer', Rule::exists('contracts', 'id')],
            'template' => ['required', Rule::in($templates)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.feature_key' => ['nullable', 'string', Rule::in($features)],
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
        if ($property === 'autoNumber' && $this->autoNumber) {
            $this->invoice_number = '';
        }
    }

    public function addItem(): void
    {
        $this->items[] = ['description' => '', 'feature_key' => null, 'quantity' => 1, 'unit_price' => 0, 'total' => 0];
    }

    public function removeItem(int $index): void
    {
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

    public function getSubtotalProperty(): float
    {
        return round(array_sum(array_map(fn ($r) => (float) ($r['total'] ?? 0), $this->items)), 2);
    }

    public function getTaxAmountProperty(): float
    {
        $rate = (float) ($this->tax_rate === '' ? 0 : $this->tax_rate);

        return round($this->subtotal * ($rate / 100), 2);
    }

    public function getTotalProperty(): float
    {
        $discount = (float) ($this->discount === '' ? 0 : $this->discount);

        return max(0, round($this->subtotal + $this->taxAmount - $discount, 2));
    }

    protected function createInvoice(string $status, bool $sendEmail, NotificationService $notifications): Invoice
    {
        $data = $this->validate();
        $client = Client::query()->findOrFail((int) $data['client_id']);

        // Constrain request/contract linkage to selected client (avoid cross-client linking)
        if ($data['request_id']) {
            $req = ServiceRequest::query()->where('client_id', $client->id)->find($data['request_id']);
            if (! $req) {
                throw \Illuminate\Validation\ValidationException::withMessages(['request_id' => 'Selected request does not belong to this client.']);
            }
        }
        if ($data['contract_id']) {
            $contract = Contract::query()->where('client_id', $client->id)->find($data['contract_id']);
            if (! $contract) {
                throw \Illuminate\Validation\ValidationException::withMessages(['contract_id' => 'Selected contract does not belong to this client.']);
            }
        }

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'request_id' => $data['request_id'],
            'contract_id' => $data['contract_id'],
            'invoice_number' => $this->autoNumber ? null : $data['invoice_number'],
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'],
            'tax_rate' => (float) ($data['tax_rate'] === '' ? 0 : $data['tax_rate']),
            'discount' => (float) ($data['discount'] === '' ? 0 : $data['discount']),
            'notes' => $data['notes'] ?: null,
            'terms' => $data['terms'] ?: null,
            'status' => $status,
            'template' => $data['template'],
        ]);

        foreach (array_values($data['items']) as $idx => $row) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $row['description'],
                'feature_key' => $row['feature_key'] ?: null,
                'quantity' => (float) $row['quantity'],
                'unit_price' => (float) $row['unit_price'],
                'total' => (float) ((float) $row['quantity'] * (float) $row['unit_price']),
                'sort_order' => $idx,
            ]);
        }

        $invoice->refresh()->load('items');
        $invoice->calculateTotals();

        if ($status === 'sent') {
            $invoice->markAsSent();
        }

        ActivityLog::log(
            "Admin created invoice: {$invoice->invoice_number}",
            $invoice,
            ['client_id' => $client->id, 'status' => $status],
            'created',
            'invoices'
        );

        if ($sendEmail) {
            $notifications->sendInvoiceNotification($invoice, 'created');
        }

        return $invoice;
    }

    public function saveDraft(NotificationService $notifications)
    {
        $invoice = $this->createInvoice('draft', false, $notifications);
        session()->flash('success', 'Invoice saved as draft.');

        return redirect()->route('admin.invoices.edit', $invoice);
    }

    public function sendToClient(NotificationService $notifications)
    {
        $invoice = $this->createInvoice('sent', true, $notifications);
        session()->flash('success', 'Invoice sent to client.');

        return redirect()->route('admin.invoices.edit', $invoice);
    }

    public function render()
    {
        $user = auth()->user();
        $staffClientIds = [];
        if ($user && $user->hasRole('staff') && ! $user->hasAnyRole(['super_admin', 'admin'])) {
            $staffClientIds = $user->assignedClientIds();
        }

        $clients = Client::query()
            ->when(! empty($staffClientIds), fn ($q) => $q->whereIn('id', $staffClientIds))
            ->orderBy('company_name')
            ->get(['id', 'company_name']);

        $requests = collect();
        $contracts = collect();
        if ($this->client_id) {
            $requests = ServiceRequest::query()->where('client_id', $this->client_id)->latest()->limit(50)->get(['id', 'title', 'status']);
            $contracts = Contract::query()->where('client_id', $this->client_id)->latest()->limit(50)->get(['id', 'title', 'status']);
        }

        return view('livewire.admin.invoices.create', [
            'clients' => $clients,
            'requests' => $requests,
            'contracts' => $contracts,
            'templates' => config('client-portal.invoice.templates', []),
            'featureOptions' => collect((array) config('features.available', []))
                ->map(fn ($v) => is_array($v) ? ($v['name'] ?? null) : null)
                ->filter()
                ->toArray(),
            'subtotal' => $this->subtotal,
            'taxAmount' => $this->taxAmount,
            'total' => $this->total,
        ])->layout('layouts.admin', ['title' => 'Create Invoice']);
    }
}
