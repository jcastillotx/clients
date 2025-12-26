<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AutomationRule;
use App\Models\AutomationRun;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Request as ServiceRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class AutomationEngine
{
    /**
     * Execute all matching automations for a trigger.
     *
     * @param array $context Example:
     *  [
     *    'request' => $request->toArray(),
     *    'client' => $request->client?->toArray(),
     *  ]
     */
    public function run(string $trigger, array $context = [], ?int $clientId = null): array
    {
        $rules = AutomationRule::query()
            ->where('trigger', $trigger)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $results = [];

        foreach ($rules as $rule) {
            $run = AutomationRun::create([
                'automation_rule_id' => $rule->id,
                'trigger' => $trigger,
                'client_id' => $clientId,
                'context' => $this->safeContext($context),
                'matched' => false,
                'succeeded' => false,
                'actions_total' => count((array) ($rule->actions ?? [])),
                'actions_succeeded' => 0,
                'actions_failed' => 0,
                'error' => null,
                'ran_at' => now(),
            ]);

            try {
                $matched = $this->evaluateConditions($rule->conditions, $context);
                $run->matched = $matched;
                $run->save();

                if (!$matched) {
                    $results[] = ['rule_id' => $rule->id, 'matched' => false, 'succeeded' => true];
                    continue;
                }

                [$ok, $succ, $fail] = $this->executeActions((array) ($rule->actions ?? []), $context);
                $run->succeeded = $ok;
                $run->actions_succeeded = $succ;
                $run->actions_failed = $fail;
                $run->save();

                $results[] = ['rule_id' => $rule->id, 'matched' => true, 'succeeded' => $ok];
            } catch (\Throwable $e) {
                $run->succeeded = false;
                $run->error = $e->getMessage();
                $run->save();
                $results[] = ['rule_id' => $rule->id, 'matched' => (bool) $run->matched, 'succeeded' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    public function evaluateConditions($conditions, array $context): bool
    {
        if (empty($conditions)) {
            return true;
        }

        // group form: {operator: and|or, rules:[...]}
        if (is_array($conditions) && isset($conditions['rules'])) {
            $op = strtolower((string) ($conditions['operator'] ?? 'and'));
            $rules = (array) ($conditions['rules'] ?? []);
            if (empty($rules)) {
                return true;
            }

            $results = array_map(fn ($r) => $this->evaluateConditions($r, $context), $rules);
            return $op === 'or' ? in_array(true, $results, true) : !in_array(false, $results, true);
        }

        // single rule: {field, operator, value}
        if (!is_array($conditions)) {
            return true;
        }

        $field = (string) ($conditions['field'] ?? '');
        $op = strtolower((string) ($conditions['operator'] ?? 'equals'));
        $expected = $conditions['value'] ?? null;
        $actual = $this->getValue($context, $field);

        return $this->compare($actual, $op, $expected);
    }

    protected function compare($actual, string $op, $expected): bool
    {
        return match ($op) {
            'equals', 'eq' => $actual == $expected,
            'not_equals', 'neq' => $actual != $expected,
            'strict_equals' => $actual === $expected,
            'in' => in_array($actual, (array) $expected, false),
            'not_in' => !in_array($actual, (array) $expected, false),
            'contains' => is_string($actual) && str_contains($actual, (string) $expected),
            'gt' => is_numeric($actual) && is_numeric($expected) && $actual > $expected,
            'gte' => is_numeric($actual) && is_numeric($expected) && $actual >= $expected,
            'lt' => is_numeric($actual) && is_numeric($expected) && $actual < $expected,
            'lte' => is_numeric($actual) && is_numeric($expected) && $actual <= $expected,
            'is_true' => (bool) $actual === true,
            'is_false' => (bool) $actual === false,
            default => true,
        };
    }

    protected function getValue(array $context, string $path)
    {
        $path = trim($path);
        if ($path === '') {
            return null;
        }
        return Arr::get($context, $path);
    }

    protected function executeActions(array $actions, array $context): array
    {
        $succ = 0;
        $fail = 0;

        foreach ($actions as $action) {
            try {
                $this->executeAction((array) $action, $context);
                $succ++;
            } catch (\Throwable $e) {
                $fail++;
                ActivityLog::log(
                    'Automation action failed: ' . $e->getMessage(),
                    null,
                    ['action' => $action, 'context' => $this->safeContext($context)],
                    'automation_action_failed',
                    'automation'
                );
            }
        }

        return [$fail === 0, $succ, $fail];
    }

    protected function executeAction(array $action, array $context): void
    {
        $type = strtolower((string) ($action['type'] ?? ''));
        $config = (array) ($action['config'] ?? []);

        match ($type) {
            'send_email' => $this->actionSendEmail($config, $context),
            'send_notification' => $this->actionSendNotification($config, $context),
            'assign_request' => $this->actionAssignRequest($config, $context),
            'change_request_status' => $this->actionChangeRequestStatus($config, $context),
            'create_invoice' => $this->actionCreateInvoice($config, $context),
            'update_client_tier' => $this->actionUpdateClientTier($config, $context),
            'add_internal_note' => $this->actionAddInternalNote($config, $context),
            'trigger_webhook' => $this->actionTriggerWebhook($config, $context),
            'create_admin_task' => $this->actionCreateAdminTask($config, $context),
            default => null,
        };
    }

    protected function actionSendEmail(array $config, array $context): void
    {
        $to = $this->renderTemplate((string) ($config['to'] ?? ''), $context);
        if ($to === '' || !str_contains($to, '@')) {
            // convenience: allow "client" to mean client email
            if (($config['to'] ?? null) === 'client') {
                $to = (string) Arr::get($context, 'client.email', '');
            }
        }
        abort_unless($to && str_contains($to, '@'), 422, 'Email action missing valid recipient.');

        $subject = $this->renderTemplate((string) ($config['subject'] ?? 'Notification'), $context);
        $body = $this->renderTemplate((string) ($config['body'] ?? ''), $context);

        Mail::raw($body, function ($m) use ($to, $subject) {
            $m->to($to)->subject($subject);
        });
    }

    protected function actionSendNotification(array $config, array $context): void
    {
        $channel = strtolower((string) ($config['channel'] ?? 'slack')); // slack|teams|sms|webhook
        $url = (string) ($config['url'] ?? '');
        $message = $this->renderTemplate((string) ($config['message'] ?? ''), $context);

        if ($channel === 'sms') {
            // placeholder: log-only
            ActivityLog::log('Automation SMS: ' . $message, null, ['to' => $config['to'] ?? null], 'automation_sms', 'automation');
            return;
        }

        abort_unless($url && str_starts_with($url, 'http'), 422, 'Notification action missing url.');

        $payload = match ($channel) {
            'teams' => [
                '@type' => 'MessageCard',
                '@context' => 'https://schema.org/extensions',
                'summary' => 'Automation',
                'title' => 'Automation',
                'text' => $message,
            ],
            default => ['text' => $message], // slack/webhook
        };

        Http::timeout(10)->post($url, $payload)->throw();
    }

    protected function actionAssignRequest(array $config, array $context): void
    {
        $requestId = (int) ($config['request_id'] ?? Arr::get($context, 'request.id', 0));
        $userId = (int) ($config['user_id'] ?? 0);
        abort_unless($requestId && $userId, 422, 'Assign request action missing request_id or user_id.');

        ServiceRequest::query()->whereKey($requestId)->update(['assigned_to' => $userId]);
    }

    protected function actionChangeRequestStatus(array $config, array $context): void
    {
        $requestId = (int) ($config['request_id'] ?? Arr::get($context, 'request.id', 0));
        $status = (string) ($config['status'] ?? '');
        abort_unless($requestId && $status, 422, 'Change status action missing request_id or status.');

        ServiceRequest::query()->whereKey($requestId)->update(['status' => $status]);
    }

    protected function actionCreateInvoice(array $config, array $context): void
    {
        $requestId = (int) ($config['request_id'] ?? Arr::get($context, 'request.id', 0));
        $clientId = (int) ($config['client_id'] ?? Arr::get($context, 'request.client_id', Arr::get($context, 'client.id', 0)));
        abort_unless($clientId, 422, 'Create invoice action missing client_id.');

        $invoice = Invoice::create([
            'client_id' => $clientId,
            'request_id' => $requestId ?: null,
            'status' => 'draft',
        ]);

        $desc = $this->renderTemplate((string) ($config['description'] ?? 'Service Request {{request.id}}: {{request.title}}'), $context);
        $amount = (float) ($config['amount'] ?? Arr::get($context, 'request.estimated_cost', 0));
        if ($amount <= 0) {
            $amount = 0;
        }

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => $desc,
            'quantity' => 1,
            'unit_price' => $amount,
            'total' => $amount,
            'sort_order' => 0,
        ]);

        $invoice->calculateTotals();
    }

    protected function actionUpdateClientTier(array $config, array $context): void
    {
        $clientId = (int) ($config['client_id'] ?? Arr::get($context, 'client.id', Arr::get($context, 'request.client_id', 0)));
        $tier = (string) ($config['tier'] ?? '');
        abort_unless($clientId && $tier, 422, 'Update tier action missing client_id or tier.');

        Client::query()->whereKey($clientId)->update(['tier' => $tier]);
    }

    protected function actionAddInternalNote(array $config, array $context): void
    {
        $message = $this->renderTemplate((string) ($config['message'] ?? 'Automation note'), $context);
        ActivityLog::log($message, null, ['context' => $this->safeContext($context)], 'automation_note', 'automation');
    }

    protected function actionCreateAdminTask(array $config, array $context): void
    {
        $message = $this->renderTemplate((string) ($config['message'] ?? 'Automation task'), $context);
        ActivityLog::log($message, null, ['context' => $this->safeContext($context)], 'automation_task', 'automation');
    }

    protected function actionTriggerWebhook(array $config, array $context): void
    {
        $event = (string) ($config['event'] ?? '');
        abort_unless($event !== '', 422, 'Trigger webhook action missing event.');
        $clientId = (int) ($config['client_id'] ?? Arr::get($context, 'client.id', Arr::get($context, 'request.client_id', 0)));

        app(WebhookService::class)->triggerWebhook($event, [
            'source' => 'automation',
            'data' => $this->safeContext($context),
        ], $clientId ?: null);
    }

    protected function renderTemplate(string $template, array $context): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.-]+)\s*\}\}/', function ($m) use ($context) {
            $v = $this->getValue($context, (string) $m[1]);
            if (is_array($v) || is_object($v)) {
                return json_encode($v, JSON_UNESCAPED_SLASHES) ?: '';
            }
            return (string) ($v ?? '');
        }, $template) ?? $template;
    }

    protected function safeContext(array $context): array
    {
        // avoid huge payloads / recursion; keep shallow known keys
        $allow = ['request', 'invoice', 'contract', 'document', 'client', 'payment', 'storage', 'meta'];
        $out = [];
        foreach ($allow as $k) {
            if (array_key_exists($k, $context)) {
                $out[$k] = $context[$k];
            }
        }
        return $out ?: $context;
    }
}

