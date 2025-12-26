<?php

namespace App\Http\Livewire\Admin\Automation;

use App\Models\AutomationRule;
use App\Services\AutomationEngine;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AutomationBuilder extends Component
{
    public ?int $ruleId = null;

    public string $name = '';
    public string $description = '';
    public bool $isActive = true;
    public string $trigger = 'request.created';
    public int $runOrder = 100;

    public string $conditionsOp = 'and';
    /** @var array<int, array{path:string,operator:string,value:mixed}> */
    public array $conditions = [];

    /**
     * @var array<int, array{type:string,params?:array,params_json?:string}>
     */
    public array $actions = [];

    public string $samplePayloadJson = "{\n  \"client_id\": 1,\n  \"request\": {\"id\": 123, \"priority\": \"urgent\", \"title\": \"Test\"}\n}\n";
    public array $testResult = [];

    public array $availableTriggers = [];
    public array $availableActionTypes = [
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

    public array $operators = [
        'equals', 'not_equals', 'contains', 'in', 'not_in', 'gt', 'gte', 'lt', 'lte', 'exists', 'not_exists',
    ];

    public function mount(?int $rule = null): void
    {
        $this->availableTriggers = $this->defaultTriggers();
        $this->ruleId = $rule;

        if ($rule) {
            $r = AutomationRule::query()->findOrFail($rule);
            $this->name = (string) $r->name;
            $this->description = (string) ($r->description ?? '');
            $this->isActive = (bool) $r->is_active;
            $this->trigger = (string) $r->trigger;
            $this->runOrder = (int) $r->run_order;

            $conds = $r->conditions ?: null;
            if (is_array($conds) && isset($conds['op'], $conds['rules']) && is_array($conds['rules'])) {
                $this->conditionsOp = (string) $conds['op'];
                $this->conditions = array_values(array_filter($conds['rules'], fn ($c) => is_array($c)));
            } else {
                $this->conditions = [];
            }

            $this->actions = is_array($r->actions) ? $r->actions : [];
            $this->actions = array_map(function ($a) {
                $params = is_array($a['params'] ?? null) ? $a['params'] : [];
                return [
                    'type' => (string) ($a['type'] ?? 'send_notification'),
                    'params_json' => json_encode($params, JSON_UNESCAPED_SLASHES),
                ];
            }, $this->actions);
            if ($this->actions === []) {
                $this->actions = [[
                    'type' => 'send_notification',
                    'params_json' => json_encode(['channel' => 'slack', 'message' => 'Automation fired: {{event}}'], JSON_UNESCAPED_SLASHES),
                ]];
            }
        } else {
            // New rule defaults
            $this->conditions = [['path' => 'request.priority', 'operator' => 'equals', 'value' => 'urgent']];
            $this->actions = [
                ['type' => 'send_notification', 'params_json' => json_encode(['channel' => 'slack', 'message' => 'Urgent request: {{request.title}} (#{{request.id}})'], JSON_UNESCAPED_SLASHES)],
            ];
        }
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'isActive' => ['boolean'],
            'trigger' => ['required', 'string', Rule::in(array_keys($this->availableTriggers))],
            'runOrder' => ['required', 'integer', 'min:0', 'max:1000000'],
            'conditionsOp' => ['required', Rule::in(['and', 'or'])],
            'conditions' => ['array'],
            'conditions.*.path' => ['required', 'string', 'max:255'],
            'conditions.*.operator' => ['required', Rule::in($this->operators)],
            'conditions.*.value' => ['nullable'],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*.type' => ['required', Rule::in(array_keys($this->availableActionTypes))],
            'actions.*.params_json' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function addCondition(): void
    {
        $this->conditions[] = ['path' => 'request.priority', 'operator' => 'equals', 'value' => 'urgent'];
    }

    public function removeCondition(int $idx): void
    {
        unset($this->conditions[$idx]);
        $this->conditions = array_values($this->conditions);
    }

    public function addAction(): void
    {
        $this->actions[] = [
            'type' => 'send_email',
            'params_json' => json_encode(['to' => 'admin', 'subject' => 'Automation fired', 'body' => 'Event: {{event}}'], JSON_UNESCAPED_SLASHES),
        ];
    }

    public function removeAction(int $idx): void
    {
        unset($this->actions[$idx]);
        $this->actions = array_values($this->actions);
    }

    public function save(): void
    {
        $data = $this->validate();

        $conditions = [
            'op' => $data['conditionsOp'],
            'rules' => array_values($data['conditions'] ?? []),
        ];

        $actions = [];
        foreach (array_values($data['actions']) as $a) {
            $params = [];
            $raw = (string) ($a['params_json'] ?? '');
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $params = $decoded;
                }
            }
            $actions[] = [
                'type' => (string) $a['type'],
                'params' => $params,
            ];
        }

        $payload = [
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'is_active' => (bool) $data['isActive'],
            'trigger' => $data['trigger'],
            'conditions' => $conditions,
            'actions' => $actions,
            'run_order' => (int) $data['runOrder'],
            'created_by' => auth()->id(),
        ];

        $rule = $this->ruleId
            ? AutomationRule::query()->findOrFail($this->ruleId)
            : new AutomationRule();

        $rule->fill($payload);
        $rule->save();

        $this->ruleId = $rule->id;
        session()->flash('success', 'Automation saved.');
    }

    public function test(AutomationEngine $engine): void
    {
        $this->testResult = [];

        $payload = json_decode($this->samplePayloadJson, true);
        if (!is_array($payload)) {
            $this->testResult = ['ok' => false, 'message' => 'Invalid JSON payload.'];
            return;
        }

        $conditions = [
            'op' => $this->conditionsOp,
            'rules' => $this->conditions,
        ];

        $matched = $engine->evaluateConditions($conditions, $payload);
        $actionResults = [];

        if ($matched) {
            foreach ($this->actions as $a) {
                $params = [];
                $raw = (string) ($a['params_json'] ?? '');
                if ($raw !== '') {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) {
                        $params = $decoded;
                    }
                }
                $actionResults[] = $engine->executeAction([
                    'type' => (string) ($a['type'] ?? ''),
                    'params' => $params,
                ], $payload, true);
            }
        }

        $this->testResult = [
            'ok' => true,
            'matched' => $matched,
            'actions' => $actionResults,
        ];
    }

    protected function defaultTriggers(): array
    {
        // 20+ trigger types (can be expanded without DB changes)
        return [
            // Requests
            'request.created' => 'Request created',
            'request.updated' => 'Request updated',
            'request.status_changed' => 'Request status changed',
            'request.assigned' => 'Request assigned',
            'request.completed' => 'Request completed',

            // Invoices
            'invoice.created' => 'Invoice created',
            'invoice.sent' => 'Invoice sent',
            'invoice.paid' => 'Invoice paid',
            'invoice.overdue' => 'Invoice overdue',
            'invoice.due_soon' => 'Invoice due date approaching (7 days)',

            // Contracts
            'contract.created' => 'Contract created',
            'contract.expiring' => 'Contract expiring',
            'contract.signed' => 'Contract signed',

            // Documents
            'document.uploaded' => 'Document uploaded',
            'document.shared' => 'Document shared',

            // Payments
            'payment.received' => 'Payment received',
            'payment.failed' => 'Payment failed',

            // Clients
            'client.created' => 'Client created',
            'client.tier_changed' => 'Client tier changed',

            // Storage
            'storage.quota_reached' => 'Storage quota reached (>=80%)',

            // Scheduled
            'schedule.daily' => 'Schedule: daily',
            'schedule.weekly' => 'Schedule: weekly',
            'schedule.monthly' => 'Schedule: monthly',
        ];
    }

    public function render()
    {
        return view('livewire.admin.automation.builder', [
            'triggerOptions' => $this->availableTriggers,
            'actionTypes' => $this->availableActionTypes,
            'operators' => $this->operators,
        ])->layout('layouts.admin', ['title' => $this->ruleId ? 'Edit Automation' : 'New Automation']);
    }
}

