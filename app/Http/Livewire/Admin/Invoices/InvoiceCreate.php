<?php

namespace App\Http\Livewire\Admin\Invoices;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\RecurringInvoice;
use App\Models\Request as ServiceRequest;
use App\Services\NotificationService;
use Illuminate\Support\Carbon;
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

    // Recurring invoice properties
    public bool $is_recurring = false;

    public string $recurring_name = '';

    public string $recurring_frequency = 'monthly';

    public ?int $recurring_day_of_month = null;

    public ?int $recurring_day_of_week = null;

    public ?string $recurring_start_date = null;

    public ?string $recurring_end_date = null;

    public ?int $recurring_occurrences_limit = null;

    public int $recurring_payment_terms_days = 30;

    public bool $recurring_auto_send = false;

    public function mount(): void
    {
        $this->issue_date = now()->toDateString();
        $this->due_date = now()->addDays(30)->toDateString();
        $this->recurring_start_date = now()->toDateString();
        $this->tax_rate = (string) config('client-portal.invoice.tax_rate', 0);
        $this->items = [
            ['description' => '', 'feature_key' => null, 'quantity' => 1, 'unit_price' => 0, 'total' => 0],
        ];
    }

    protected function rules(): array
    {
        $templates = array_keys(config('client-portal.invoice.templates', ['classic' => 'Classic']));
        $features = array_keys((array) config('features.available', []));

        $rules = [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')],
            'autoNumber' => ['boolean'],
            'invoice_number' => [$this->autoNumber ? 'nullable' : 'required', 'string', 'max:255', Rule::unique('invoices', 'invoice_number')],
            'issue_date' => ['required_without:is_recurring', 'nullable', 'date'],
            'due_date' => ['required_without:is_recurring', 'nullable', 'date', 'after_or_equal:issue_date'],
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
            'is_recurring' => ['boolean'],
        ];

        // Add recurring-specific rules when recurring is enabled
        if ($this->is_recurring) {
            $rules['recurring_name'] = ['required', 'string', 'max:255'];
            $rules['recurring_frequency'] = ['required', Rule::in(array_keys(RecurringInvoice::frequencyOptions()))];
            $rules['recurring_start_date'] = ['required', 'date', 'after_or_equal:today'];
            $rules['recurring_end_date'] = ['nullable', 'date', 'after:recurring_start_date'];
            $rules['recurring_day_of_month'] = ['nullable', 'integer', 'min:1', 'max:28'];
            $rules['recurring_day_of_week'] = ['nullable', 'integer', 'min:0', 'max:6'];
            $rules['recurring_occurrences_limit'] = ['nullable', 'integer', 'min:1', 'max:999'];
            $rules['recurring_payment_terms_days'] = ['required', 'integer', 'min:0', 'max:365'];
            $rules['recurring_auto_send'] = ['boolean'];
        }

        return $rules;
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'items.')) {
            $this->recalculate();
        }
        if ($property === 'autoNumber' && $this->autoNumber) {
            $this->invoice_number = '';
        }
        if ($property === 'recurring_frequency') {
            // Reset day fields when frequency changes
            $this->recurring_day_of_month = null;
            $this->recurring_day_of_week = null;
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

    protected function createRecurringInvoice(): RecurringInvoice
    {
        $data = $this->validate();
        $client = Client::query()->findOrFail((int) $data['client_id']);

        // Constrain request/contract linkage to selected client
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

        // Prepare line items for storage
        $lineItems = array_map(function ($item) {
            return [
                'description' => $item['description'],
                'feature_key' => $item['feature_key'] ?: null,
                'quantity' => (float) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
            ];
        }, array_values($data['items']));

        // Calculate next generate date based on start date
        $startDate = Carbon::parse($data['recurring_start_date']);

        $recurring = RecurringInvoice::create([
            'client_id' => $client->id,
            'request_id' => $data['request_id'],
            'contract_id' => $data['contract_id'],
            'name' => $data['recurring_name'],
            'frequency' => $data['recurring_frequency'],
            'day_of_month' => $data['recurring_day_of_month'],
            'day_of_week' => $data['recurring_day_of_week'],
            'start_date' => $startDate,
            'end_date' => $data['recurring_end_date'] ? Carbon::parse($data['recurring_end_date']) : null,
            'next_generate_date' => $startDate,
            'occurrences_limit' => $data['recurring_occurrences_limit'],
            'occurrences_count' => 0,
            'tax_rate' => (float) ($data['tax_rate'] === '' ? 0 : $data['tax_rate']),
            'discount' => (float) ($data['discount'] === '' ? 0 : $data['discount']),
            'notes' => $data['notes'] ?: null,
            'terms' => $data['terms'] ?: null,
            'template' => $data['template'],
            'payment_terms_days' => $data['recurring_payment_terms_days'],
            'line_items' => $lineItems,
            'status' => 'active',
            'auto_send' => $data['recurring_auto_send'],
        ]);

        ActivityLog::log(
            "Admin created recurring invoice: {$recurring->name}",
            $recurring,
            [
                'client_id' => $client->id,
                'frequency' => $recurring->frequency,
                'auto_send' => $recurring->auto_send,
            ],
            'created',
            'recurring_invoices'
        );

        return $recurring;
    }

    public function saveDraft(NotificationService $notifications)
    {
        if ($this->is_recurring) {
            $recurring = $this->createRecurringInvoice();
            session()->flash('success', 'Recurring invoice schedule created.');

            return redirect()->route('admin.invoices.recurring.index');
        }

        $invoice = $this->createInvoice('draft', false, $notifications);
        session()->flash('success', 'Invoice saved as draft.');

        return redirect()->route('admin.invoices.edit', $invoice);
    }

    public function sendToClient(NotificationService $notifications)
    {
        if ($this->is_recurring) {
            $recurring = $this->createRecurringInvoice();
            session()->flash('success', 'Recurring invoice schedule created and will auto-send when generated.');

            return redirect()->route('admin.invoices.recurring.index');
        }

        $invoice = $this->createInvoice('sent', true, $notifications);
        session()->flash('success', 'Invoice sent to client.');

        return redirect()->route('admin.invoices.edit', $invoice);
    }

    public function saveRecurring()
    {
        $this->is_recurring = true;
        $recurring = $this->createRecurringInvoice();
        session()->flash('success', 'Recurring invoice schedule created.');

        return redirect()->route('admin.invoices.recurring.index');
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
            'frequencyOptions' => RecurringInvoice::frequencyOptions(),
            'dayOfWeekOptions' => [
                0 => 'Sunday',
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday',
            ],
        ])->layout('layouts.admin', ['title' => 'Create Invoice']);
    }
}
