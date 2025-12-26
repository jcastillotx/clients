<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AutomationLog;
use App\Models\AutomationRule;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Request as ServiceRequest;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class AutomationEngine
{
    /**
     * Fire a trigger and run matching automation rules.
     *
     * @param  array<string,mixed>  $payload
     * @return array<int, array<string,mixed>>  Per-rule results
     */
    public function trigger(string $trigger, array $payload, bool $dryRun = false): array
    {
        $rules = AutomationRule::query()
            ->where('is_active', true)
            ->where('trigger', $trigger)
            ->orderBy('run_order')
            ->orderBy('id')
            ->get();

        $results = [];

        foreach ($rules as $rule) {
            $log = AutomationLog::create([
                'automation_rule_id' => $rule->id,
                'trigger' => $trigger,
                'status' => $dryRun ? 'dry_run' : 'succeeded',
                'started_at' => now(),
                'context' => [
                    'payload' => $payload,
                    'rule' => [
                        'id' => $rule->id,
                        'name' => $rule->name,
                    ],
                ],
            ]);

            $ok = false;
            $msg = null;
            $actionResults = [];

            try {
                $ok = $this->evaluateConditions($rule->conditions, $payload);
                if (!$ok) {
                    $log->update([
                        'status' => 'skipped',
                        'message' => 'Conditions not met.',
                        'finished_at' => now(),
                    ]);
                    $results[] = ['rule_id' => $rule->id, 'status' => 'skipped'];
                    continue;
                }

                $actions = (array) ($rule->actions ?? []);
                foreach ($actions as $action) {
                    $actionResults[] = $this->executeAction($action, $payload, $dryRun);
                }

                $rule->update(['last_ran_at' => now()]);
                $msg = $dryRun ? 'Dry run complete.' : 'Executed.';

                $log->update([
                    'status' => $dryRun ? 'dry_run' : 'succeeded',
                    'message' => $msg,
                    'finished_at' => now(),
                    'context' => array_merge($log->context ?? [], [
                        'actions' => $actions,
                        'results' => $actionResults,
                    ]),
                ]);

                $results[] = ['rule_id' => $rule->id, 'status' => $dryRun ? 'dry_run' : 'succeeded', 'results' => $actionResults];
            } catch (\Throwable $e) {
                $log->update([
                    'status' => 'failed',
                    'message' => mb_substr($e->getMessage(), 0, 500),
                    'finished_at' => now(),
                    'context' => array_merge($log->context ?? [], [
                        'exception' => [
                            'class' => get_class($e),
                            'message' => $e->getMessage(),
                        ],
                        'results' => $actionResults,
                    ]),
                ]);
                $results[] = ['rule_id' => $rule->id, 'status' => 'failed', 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Condition DSL:
     * - null/empty => true
     * - Group: ['op' => 'and'|'or', 'rules' => [cond|group...]]
     * - Condition: ['path' => 'request.priority', 'operator' => 'equals', 'value' => 'urgent']
     *
     * Supported operators: equals, not_equals, contains, in, not_in, gt, gte, lt, lte, exists, not_exists
     *
     * @param  array<string,mixed>|null  $conditions
     * @param  array<string,mixed>  $payload
     */
    public function evaluateConditions(?array $conditions, array $payload): bool
    {
        if (!$conditions || $conditions === []) {
            return true;
        }

        // Group form
        if (isset($conditions['op']) && isset($conditions['rules']) && is_array($conditions['rules'])) {
            $op = strtolower((string) $conditions['op']);
            $rules = $conditions['rules'];

            if ($op === 'or') {
                foreach ($rules as $r) {
                    if ($this->evaluateConditions(is_array($r) ? $r : null, $payload)) {
                        return true;
                    }
                }
                return false;
            }

            // default AND
            foreach ($rules as $r) {
                if (!$this->evaluateConditions(is_array($r) ? $r : null, $payload)) {
                    return false;
                }
            }
            return true;
        }

        // Single condition form
        $path = (string) ($conditions['path'] ?? '');
        $operator = strtolower((string) ($conditions['operator'] ?? 'equals'));
        $expected = $conditions['value'] ?? null;

        $actual = Arr::get($payload, $path);

        return $this->compare($actual, $operator, $expected);
    }

    protected function compare(mixed $actual, string $operator, mixed $expected): bool
    {
        return match ($operator) {
            'exists' => Arr::accessible([$actual]) ? true : ($actual !== null),
            'not_exists' => $actual === null,
            'equals' => $actual == $expected,
            'not_equals' => $actual != $expected,
            'contains' => is_string($actual) && is_string($expected) ? str_contains($actual, $expected) : false,
            'in' => is_array($expected) ? in_array($actual, $expected, false) : false,
            'not_in' => is_array($expected) ? !in_array($actual, $expected, false) : false,
            'gt' => is_numeric($actual) && is_numeric($expected) ? ((float) $actual > (float) $expected) : false,
            'gte' => is_numeric($actual) && is_numeric($expected) ? ((float) $actual >= (float) $expected) : false,
            'lt' => is_numeric($actual) && is_numeric($expected) ? ((float) $actual < (float) $expected) : false,
            'lte' => is_numeric($actual) && is_numeric($expected) ? ((float) $actual <= (float) $expected) : false,
            default => false,
        };
    }

    /**
     * @param  array<string,mixed>  $action
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    public function executeAction(array $action, array $payload, bool $dryRun): array
    {
        $type = (string) ($action['type'] ?? '');
        $params = (array) ($action['params'] ?? []);

        return match ($type) {
            'send_notification' => $this->actionSendNotification($params, $payload, $dryRun),
            'send_email' => $this->actionSendEmail($params, $payload, $dryRun),
            'assign_request' => $this->actionAssignRequest($params, $payload, $dryRun),
            'change_request_status' => $this->actionChangeRequestStatus($params, $payload, $dryRun),
            'create_invoice' => $this->actionCreateInvoice($params, $payload, $dryRun),
            'update_client_tier' => $this->actionUpdateClientTier($params, $payload, $dryRun),
            'add_internal_note' => $this->actionAddInternalNote($params, $payload, $dryRun),
            'trigger_webhook' => $this->actionTriggerWebhook($params, $payload, $dryRun),
            'create_admin_task' => $this->actionCreateAdminTask($params, $payload, $dryRun),
            default => ['type' => $type, 'ok' => false, 'message' => 'Unknown action type'],
        };
    }

    protected function actionSendNotification(array $params, array $payload, bool $dryRun): array
    {
        $channel = (string) ($params['channel'] ?? 'slack'); // slack|teams|sms (sms is no-op placeholder)
        $message = (string) ($params['message'] ?? 'Automation notification');
        $message = $this->renderTemplate($message, $payload);

        if ($dryRun) {
            return ['type' => 'send_notification', 'ok' => true, 'dry_run' => true, 'channel' => $channel, 'message' => $message];
        }

        if ($channel === 'sms') {
            return ['type' => 'send_notification', 'ok' => false, 'message' => 'SMS not configured'];
        }

        $urlKey = $channel === 'teams' ? 'notify.teams.webhook' : 'notify.slack.webhook';
        $webhook = (string) Setting::getValue($urlKey, '');
        if ($webhook === '') {
            return ['type' => 'send_notification', 'ok' => false, 'message' => "Missing setting {$urlKey}"];
        }

        Http::timeout(5)->post($webhook, ['text' => $message]);
        return ['type' => 'send_notification', 'ok' => true, 'channel' => $channel];
    }

    protected function actionSendEmail(array $params, array $payload, bool $dryRun): array
    {
        $to = (string) ($params['to'] ?? 'admin'); // admin|client|email
        $email = (string) ($params['email'] ?? '');
        $subject = (string) ($params['subject'] ?? 'Notification');
        $body = (string) ($params['body'] ?? '');

        $subject = $this->renderTemplate($subject, $payload);
        $body = $this->renderTemplate($body, $payload);

        $recipients = [];
        if ($to === 'email' && $email !== '') {
            $recipients = [$email];
        } elseif ($to === 'admin') {
            $recipients = User::query()->role(['admin', 'super_admin'])->pluck('email')->filter()->values()->all();
        } elseif ($to === 'client') {
            $clientId = (int) Arr::get($payload, 'client.id', Arr::get($payload, 'client_id', 0));
            if ($clientId > 0) {
                $recipients = User::query()->where('client_id', $clientId)->role(['client'])->pluck('email')->filter()->values()->all();
            }
        }

        if ($dryRun) {
            return ['type' => 'send_email', 'ok' => true, 'dry_run' => true, 'to' => $to, 'recipients' => $recipients, 'subject' => $subject];
        }

        foreach ($recipients as $r) {
            Mail::raw($body, fn ($m) => $m->to($r)->subject($subject));
        }

        return ['type' => 'send_email', 'ok' => true, 'count' => count($recipients)];
    }

    protected function actionAssignRequest(array $params, array $payload, bool $dryRun): array
    {
        $requestId = (int) ($params['request_id'] ?? Arr::get($payload, 'request.id', Arr::get($payload, 'id', 0)));
        $assigneeUserId = (int) ($params['assignee_user_id'] ?? 0);
        $assigneeRole = (string) ($params['assignee_role'] ?? 'staff'); // staff|admin

        $req = ServiceRequest::query()->find($requestId);
        if (!$req) return ['type' => 'assign_request', 'ok' => false, 'message' => 'Request not found'];

        if ($assigneeUserId <= 0) {
            $assigneeUserId = (int) User::query()
                ->where('is_active', true)
                ->role([$assigneeRole])
                ->orderBy('id')
                ->value('id');
        }

        if ($assigneeUserId <= 0) return ['type' => 'assign_request', 'ok' => false, 'message' => 'No assignee found'];

        if ($dryRun) {
            return ['type' => 'assign_request', 'ok' => true, 'dry_run' => true, 'request_id' => $req->id, 'assigned_to' => $assigneeUserId];
        }

        $req->update(['assigned_to' => $assigneeUserId]);
        return ['type' => 'assign_request', 'ok' => true, 'request_id' => $req->id, 'assigned_to' => $assigneeUserId];
    }

    protected function actionChangeRequestStatus(array $params, array $payload, bool $dryRun): array
    {
        $requestId = (int) ($params['request_id'] ?? Arr::get($payload, 'request.id', Arr::get($payload, 'id', 0)));
        $status = (string) ($params['status'] ?? '');
        if ($status === '') return ['type' => 'change_request_status', 'ok' => false, 'message' => 'Missing status'];

        $req = ServiceRequest::query()->find($requestId);
        if (!$req) return ['type' => 'change_request_status', 'ok' => false, 'message' => 'Request not found'];

        if ($dryRun) {
            return ['type' => 'change_request_status', 'ok' => true, 'dry_run' => true, 'request_id' => $req->id, 'status' => $status];
        }

        $req->update(['status' => $status]);
        return ['type' => 'change_request_status', 'ok' => true, 'request_id' => $req->id, 'status' => $status];
    }

    protected function actionCreateInvoice(array $params, array $payload, bool $dryRun): array
    {
        $clientId = (int) ($params['client_id'] ?? Arr::get($payload, 'client.id', Arr::get($payload, 'client_id', 0)));
        $requestId = (int) ($params['request_id'] ?? Arr::get($payload, 'request.id', Arr::get($payload, 'id', 0)));
        $status = (string) ($params['status'] ?? 'draft');

        if ($clientId <= 0) return ['type' => 'create_invoice', 'ok' => false, 'message' => 'Missing client_id'];

        $req = $requestId ? ServiceRequest::query()->find($requestId) : null;

        $hourlyRate = (float) Setting::getValue('billing.hourly_rate', 100);
        $hours = (float) ($req?->actual_hours ?? $req?->estimated_hours ?? 0);
        $amount = $hours > 0 ? round($hours * $hourlyRate, 2) : (float) ($params['amount'] ?? 0);

        $items = (array) ($params['items'] ?? []);
        if ($items === []) {
            $items = [[
                'description' => $req ? ("Work for request: " . $req->title) : 'Service work',
                'quantity' => $hours > 0 ? $hours : 1,
                'unit_price' => $hours > 0 ? $hourlyRate : $amount,
            ]];
        }

        if ($dryRun) {
            return ['type' => 'create_invoice', 'ok' => true, 'dry_run' => true, 'client_id' => $clientId, 'request_id' => $requestId, 'amount' => $amount, 'items' => $items];
        }

        $invoice = Invoice::create([
            'client_id' => $clientId,
            'request_id' => $requestId ?: null,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => $status,
            'tax_rate' => 0,
            'discount' => 0,
        ]);

        $sort = 1;
        foreach ($items as $it) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => (string) ($it['description'] ?? 'Item'),
                'quantity' => (float) ($it['quantity'] ?? 1),
                'unit_price' => (float) ($it['unit_price'] ?? 0),
                'sort_order' => $sort++,
            ]);
        }

        $invoice->refresh();
        $invoice->calculateTotals();
        $invoice->refresh();

        return ['type' => 'create_invoice', 'ok' => true, 'invoice_id' => $invoice->id, 'amount' => (float) $invoice->amount];
    }

    protected function actionUpdateClientTier(array $params, array $payload, bool $dryRun): array
    {
        $clientId = (int) ($params['client_id'] ?? Arr::get($payload, 'client.id', Arr::get($payload, 'client_id', 0)));
        $tier = (string) ($params['tier'] ?? '');
        if ($clientId <= 0 || $tier === '') return ['type' => 'update_client_tier', 'ok' => false, 'message' => 'Missing client_id/tier'];

        $client = Client::query()->find($clientId);
        if (!$client) return ['type' => 'update_client_tier', 'ok' => false, 'message' => 'Client not found'];

        if ($dryRun) {
            return ['type' => 'update_client_tier', 'ok' => true, 'dry_run' => true, 'client_id' => $clientId, 'tier' => $tier];
        }

        $client->update(['tier' => $tier]);
        return ['type' => 'update_client_tier', 'ok' => true, 'client_id' => $clientId, 'tier' => $tier];
    }

    protected function actionAddInternalNote(array $params, array $payload, bool $dryRun): array
    {
        $message = (string) ($params['message'] ?? 'Automation note');
        $message = $this->renderTemplate($message, $payload);

        $subjectType = (string) ($params['subject_type'] ?? '');
        $subjectId = (int) ($params['subject_id'] ?? 0);

        // Convenience: infer from payload
        if ($subjectType === '' && Arr::has($payload, 'request.id')) {
            $subjectType = ServiceRequest::class;
            $subjectId = (int) Arr::get($payload, 'request.id');
        }
        if ($subjectType === '' && Arr::has($payload, 'invoice.id')) {
            $subjectType = Invoice::class;
            $subjectId = (int) Arr::get($payload, 'invoice.id');
        }

        if ($dryRun) {
            return ['type' => 'add_internal_note', 'ok' => true, 'dry_run' => true, 'subject_type' => $subjectType, 'subject_id' => $subjectId, 'message' => $message];
        }

        ActivityLog::create([
            'user_id' => null,
            'client_id' => (int) Arr::get($payload, 'client.id', Arr::get($payload, 'client_id', null)),
            'action' => 'automation_note',
            'description' => $message,
            'subject_type' => $subjectType ?: null,
            'subject_id' => $subjectId ?: null,
            'metadata' => ['automation' => true],
        ]);

        return ['type' => 'add_internal_note', 'ok' => true];
    }

    protected function actionTriggerWebhook(array $params, array $payload, bool $dryRun): array
    {
        $event = (string) ($params['event'] ?? '');
        $clientId = (int) ($params['client_id'] ?? Arr::get($payload, 'client.id', Arr::get($payload, 'client_id', 0)));
        $data = (array) ($params['data'] ?? $payload);

        if ($event === '' || $clientId <= 0) return ['type' => 'trigger_webhook', 'ok' => false, 'message' => 'Missing event/client_id'];
        if ($dryRun) return ['type' => 'trigger_webhook', 'ok' => true, 'dry_run' => true, 'event' => $event, 'client_id' => $clientId];

        app(WebhookService::class)->triggerWebhook($event, $data, $clientId);
        return ['type' => 'trigger_webhook', 'ok' => true, 'event' => $event];
    }

    protected function actionCreateAdminTask(array $params, array $payload, bool $dryRun): array
    {
        $title = (string) ($params['title'] ?? 'Automation task');
        $title = $this->renderTemplate($title, $payload);
        $details = (string) ($params['details'] ?? '');
        $details = $this->renderTemplate($details, $payload);

        if ($dryRun) {
            return ['type' => 'create_admin_task', 'ok' => true, 'dry_run' => true, 'title' => $title];
        }

        ActivityLog::create([
            'user_id' => null,
            'client_id' => (int) Arr::get($payload, 'client.id', Arr::get($payload, 'client_id', null)),
            'action' => 'automation_task',
            'description' => $title,
            'subject_type' => null,
            'subject_id' => null,
            'metadata' => [
                'automation' => true,
                'details' => $details,
            ],
        ]);

        return ['type' => 'create_admin_task', 'ok' => true];
    }

    protected function renderTemplate(string $template, array $payload): string
    {
        // Very small templating: replaces {{path.to.value}}
        return preg_replace_callback('/\{\{\s*([A-Za-z0-9_.-]+)\s*\}\}/', function ($m) use ($payload) {
            $v = Arr::get($payload, $m[1]);
            if (is_array($v) || is_object($v)) return '';
            return (string) ($v ?? '');
        }, $template) ?? $template;
    }
}

