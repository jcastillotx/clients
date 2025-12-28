<?php

namespace App\Http\Livewire\Admin\Automation;

use App\Models\AutomationRule;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Request as ServiceRequest;
use App\Models\User;
use App\Services\AutomationEngine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class AutomationBuilder extends Component
{
    public ?int $ruleId = null;

    public string $name = '';

    public string $description = '';

    public string $trigger = 'request.created';

    public bool $is_active = true;

    public int $sort_order = 0;

    // Condition group: operator + rules[]
    public string $conditions_operator = 'and';

    public array $conditions = []; // each: [field, operator, value]

    // Actions: each: [type, config]
    public array $actions = [];

    // test
    public array $testResult = [];

    public function mount(?int $rule = null): void
    {
        abort_unless(Auth::user()?->can('access admin panel'), 403);

        $this->ruleId = $rule;
        if ($this->ruleId) {
            $r = AutomationRule::query()->findOrFail($this->ruleId);
            $this->name = (string) $r->name;
            $this->description = (string) ($r->description ?? '');
            $this->trigger = (string) $r->trigger;
            $this->is_active = (bool) $r->is_active;
            $this->sort_order = (int) $r->sort_order;

            $group = (array) ($r->conditions ?? []);
            $this->conditions_operator = (string) ($group['operator'] ?? 'and');
            $this->conditions = (array) ($group['rules'] ?? []);
            $this->actions = (array) ($r->actions ?? []);
        }

        if (empty($this->conditions)) {
            $this->addCondition();
        }
        if (empty($this->actions)) {
            $this->addAction();
        }
    }

    public function addCondition(): void
    {
        $this->conditions[] = ['field' => 'request.priority', 'operator' => 'equals', 'value' => 'urgent'];
    }

    public function removeCondition(int $idx): void
    {
        unset($this->conditions[$idx]);
        $this->conditions = array_values($this->conditions);
    }

    public function addAction(): void
    {
        $this->actions[] = ['type' => 'add_internal_note', 'config' => ['message' => 'Automation fired for {{trigger}}']];
    }

    public function removeAction(int $idx): void
    {
        unset($this->actions[$idx]);
        $this->actions = array_values($this->actions);
    }

    public function save(): void
    {
        $user = Auth::user();
        abort_unless($user?->can('access admin panel'), 403);

        $data = Validator::make([
            'name' => $this->name,
            'description' => $this->description,
            'trigger' => $this->trigger,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'conditions_operator' => $this->conditions_operator,
            'conditions' => $this->conditions,
            'actions' => $this->actions,
        ], [
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'trigger' => ['required', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:99999'],
            'conditions_operator' => ['required', 'in:and,or'],
            'conditions' => ['array'],
            'conditions.*.field' => ['required', 'string'],
            'conditions.*.operator' => ['required', 'string'],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*.type' => ['required', 'string'],
        ])->validate();

        $payload = [
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'trigger' => $data['trigger'],
            'conditions' => [
                'operator' => $data['conditions_operator'],
                'rules' => array_values((array) $data['conditions']),
            ],
            'actions' => array_values((array) $data['actions']),
            'is_active' => (bool) $data['is_active'],
            'sort_order' => (int) $data['sort_order'],
            'updated_by' => $user->id,
        ];

        if ($this->ruleId) {
            AutomationRule::query()->whereKey($this->ruleId)->update($payload);
        } else {
            $payload['created_by'] = $user->id;
            $this->ruleId = AutomationRule::create($payload)->id;
        }

        session()->flash('success', 'Automation saved.');
    }

    public function testRun(): void
    {
        $ctx = $this->sampleContextForTrigger($this->trigger);
        $ctx['meta']['trigger'] = $this->trigger;

        // run against a "synthetic" rule definition (not persisted)
        $rule = new AutomationRule([
            'trigger' => $this->trigger,
            'conditions' => ['operator' => $this->conditions_operator, 'rules' => $this->conditions],
            'actions' => $this->actions,
            'is_active' => true,
        ]);

        $engine = app(AutomationEngine::class);
        $matched = $engine->evaluateConditions($rule->conditions, $ctx);

        $this->testResult = [
            'matched' => $matched,
            'sample_keys' => array_keys($ctx),
            'note' => $matched ? 'Conditions matched. Actions will execute when saved and enabled.' : 'Conditions did not match.',
        ];
    }

    public function getTriggerOptionsProperty(): array
    {
        return [
            // Request
            'request.created',
            'request.updated',
            'request.status_changed',
            'request.assigned',
            'request.completed',
            // Invoice
            'invoice.created',
            'invoice.sent',
            'invoice.paid',
            'invoice.overdue',
            'invoice.due_approaching',
            // Contract
            'contract.created',
            'contract.expiring',
            'contract.signed',
            // Document
            'document.uploaded',
            'document.shared',
            // Payment
            'payment.received',
            'payment.failed',
            // Client
            'client.created',
            'client.tier_changed',
            // Storage
            'storage.quota_reached',
            // Schedule
            'schedule.daily',
            'schedule.weekly',
            'schedule.monthly',
        ];
    }

    public function getActionOptionsProperty(): array
    {
        return [
            'send_email' => 'Send email',
            'send_notification' => 'Send notification (Slack/Teams/SMS)',
            'assign_request' => 'Assign request to staff',
            'change_request_status' => 'Change request status',
            'create_invoice' => 'Create invoice',
            'update_client_tier' => 'Update client tier',
            'add_internal_note' => 'Add internal note',
            'trigger_webhook' => 'Trigger webhook',
            'create_admin_task' => 'Create task for admin',
        ];
    }

    public function getStaffOptionsProperty(): array
    {
        return User::query()->whereNull('client_id')->orderBy('name')->get(['id', 'name'])->toArray();
    }

    protected function sampleContextForTrigger(string $trigger): array
    {
        $meta = ['trigger' => $trigger];

        return match (true) {
            str_starts_with($trigger, 'request.') => $this->ctxFromRequest($meta),
            str_starts_with($trigger, 'invoice.') => $this->ctxFromInvoice($meta),
            str_starts_with($trigger, 'contract.') => $this->ctxFromContract($meta),
            str_starts_with($trigger, 'document.') => $this->ctxFromDocument($meta),
            str_starts_with($trigger, 'payment.') => $this->ctxFromPayment($meta),
            str_starts_with($trigger, 'client.') => $this->ctxFromClient($meta),
            str_starts_with($trigger, 'storage.') => $this->ctxFromStorage($meta),
            default => ['meta' => $meta],
        };
    }

    protected function ctxFromRequest(array $meta): array
    {
        $req = ServiceRequest::query()->with('client')->latest('id')->first();

        return [
            'meta' => $meta,
            'request' => $req?->toArray() ?? ['id' => 0, 'priority' => 'urgent', 'status' => 'pending', 'title' => 'Sample Request'],
            'client' => $req?->client?->toArray() ?? Client::query()->latest('id')->first()?->toArray(),
        ];
    }

    protected function ctxFromInvoice(array $meta): array
    {
        $inv = Invoice::query()->with('client')->latest('id')->first();

        return [
            'meta' => $meta,
            'invoice' => $inv?->toArray() ?? ['id' => 0, 'status' => 'sent', 'amount' => 100, 'due_date' => now()->addDays(7)->toDateString()],
            'client' => $inv?->client?->toArray() ?? Client::query()->latest('id')->first()?->toArray(),
        ];
    }

    protected function ctxFromContract(array $meta): array
    {
        $c = Contract::query()->with('client')->latest('id')->first();

        return [
            'meta' => $meta,
            'contract' => $c?->toArray() ?? ['id' => 0, 'status' => 'active', 'end_date' => now()->addDays(30)->toDateString()],
            'client' => $c?->client?->toArray() ?? Client::query()->latest('id')->first()?->toArray(),
        ];
    }

    protected function ctxFromDocument(array $meta): array
    {
        $d = Document::query()->with('client')->latest('id')->first();

        return [
            'meta' => $meta,
            'document' => $d?->toArray() ?? ['id' => 0, 'title' => 'Sample Doc', 'original_filename' => 'sample.pdf'],
            'client' => $d?->client?->toArray() ?? Client::query()->latest('id')->first()?->toArray(),
        ];
    }

    protected function ctxFromPayment(array $meta): array
    {
        $p = Payment::query()->with(['client', 'invoice'])->latest('id')->first();

        return [
            'meta' => $meta,
            'payment' => $p?->toArray() ?? ['id' => 0, 'status' => 'succeeded', 'amount' => 50],
            'invoice' => $p?->invoice?->toArray(),
            'client' => $p?->client?->toArray() ?? Client::query()->latest('id')->first()?->toArray(),
        ];
    }

    protected function ctxFromClient(array $meta): array
    {
        $c = Client::query()->latest('id')->first();

        return [
            'meta' => $meta,
            'client' => $c?->toArray() ?? ['id' => 0, 'tier' => 'basic', 'company_name' => 'Sample Co', 'email' => 'client@example.com'],
        ];
    }

    protected function ctxFromStorage(array $meta): array
    {
        // keep it simple (storage module exists, but not required for test run UI)
        $c = Client::query()->latest('id')->first();

        return [
            'meta' => $meta,
            'client' => $c?->toArray(),
            'storage' => ['percent' => 85, 'used_bytes' => 850, 'quota_bytes' => 1000, 'provider' => 's3'],
        ];
    }

    public function render()
    {
        return view('livewire.admin.automation.builder', [
            'triggerOptions' => $this->triggerOptions,
            'actionOptions' => $this->actionOptions,
            'staffOptions' => $this->staffOptions,
        ])->layout('layouts.admin');
    }
}
