<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Invoice;
use App\Models\Contract;
use App\Models\Client;
use App\Models\AutomationRule;
use App\Models\ReportTemplate;
use App\Models\StorageConnection;
use App\Models\User;
use App\Mail\InvoiceReminderMail;
use App\Jobs\SyncStorageConnection;
use App\Jobs\SendScheduledReport;
use App\Models\Setting;
use App\Services\AutomationEngine;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Mail;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Check for overdue invoices daily
Schedule::call(function () {
    $engine = app(AutomationEngine::class);
    $hasAutomation = AutomationRule::query()->where('is_active', true)->where('trigger', 'invoice.overdue')->exists();

    Invoice::query()
        ->where('status', 'sent')
        ->where('due_date', '<', now())
        ->with(['client'])
        ->chunkById(200, function ($invoices) use ($engine, $hasAutomation) {
            foreach ($invoices as $invoice) {
                $invoice->update(['status' => 'overdue']);

                $payload = [
                    'event' => 'invoice.overdue',
                    'client_id' => $invoice->client_id,
                    'invoice' => [
                        'id' => $invoice->id,
                        'client_id' => $invoice->client_id,
                        'invoice_number' => $invoice->invoice_number,
                        'status' => $invoice->status,
                        'amount' => (float) $invoice->amount,
                        'due_date' => $invoice->due_date?->toDateString(),
                    ],
                ];

                $engine->trigger('invoice.overdue', $payload);

                // If automations exist, do not also send the built-in reminder here.
                if (!$hasAutomation) {
                    $recipients = User::query()
                        ->where('client_id', $invoice->client_id)
                        ->role(['client'])
                        ->get();

                    foreach ($recipients as $user) {
                        if (!$user->email) continue;
                        Mail::to($user->email)->queue(new InvoiceReminderMail($invoice, 'overdue'));
                    }
                }
            }
        });
})->daily()->name('check-overdue-invoices');

// Invoice reminders (daily)
Schedule::call(function () {
    $today = now()->startOfDay();
    $engine = app(AutomationEngine::class);
    $hasDueSoonAutomation = AutomationRule::query()->where('is_active', true)->where('trigger', 'invoice.due_soon')->exists();
    $hasOverdueAutomation = AutomationRule::query()->where('is_active', true)->where('trigger', 'invoice.overdue')->exists();

    // 7 days before due date (sent invoices only)
    $dueSoonDate = $today->copy()->addDays(7)->toDateString();
    $dueSoon = Invoice::query()
        ->where('status', 'sent')
        ->whereDate('due_date', $dueSoonDate)
        ->whereNull('reminded_due_7_at')
        ->with(['client'])
        ->get();

    foreach ($dueSoon as $invoice) {
        $payload = [
            'event' => 'invoice.due_soon',
            'client_id' => $invoice->client_id,
            'invoice' => [
                'id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'amount' => (float) $invoice->amount,
                'due_date' => $invoice->due_date?->toDateString(),
            ],
        ];
        $engine->trigger('invoice.due_soon', $payload);

        if (!$hasDueSoonAutomation) {
            $recipients = User::query()
                ->where('client_id', $invoice->client_id)
                ->role(['client'])
                ->get();

            foreach ($recipients as $user) {
                if (!$user->email) continue;
                Mail::to($user->email)->queue(new InvoiceReminderMail($invoice, 'due_soon'));
            }
        }

        $invoice->update(['reminded_due_7_at' => now()]);
    }

    // 3 days after overdue (overdue invoices only)
    $overdueDate = $today->copy()->subDays(3)->toDateString();
    $overdue = Invoice::query()
        ->where('status', 'overdue')
        ->whereDate('due_date', $overdueDate)
        ->whereNull('reminded_overdue_3_at')
        ->with(['client'])
        ->get();

    foreach ($overdue as $invoice) {
        $payload = [
            'event' => 'invoice.overdue',
            'client_id' => $invoice->client_id,
            'invoice' => [
                'id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'amount' => (float) $invoice->amount,
                'due_date' => $invoice->due_date?->toDateString(),
            ],
        ];
        $engine->trigger('invoice.overdue', $payload);

        if (!$hasOverdueAutomation) {
            $recipients = User::query()
                ->where('client_id', $invoice->client_id)
                ->role(['client'])
                ->get();

            foreach ($recipients as $user) {
                if (!$user->email) continue;
                Mail::to($user->email)->queue(new InvoiceReminderMail($invoice, 'overdue'));
            }
        }

        $invoice->update(['reminded_overdue_3_at' => now()]);
    }
})->daily()->name('invoice-reminders');

// Contract expiring webhooks (daily)
Schedule::call(function () {
    $days = 30;
    $webhooks = app(WebhookService::class);
    $engine = app(AutomationEngine::class);

    Contract::query()
        ->active()
        ->whereNotNull('end_date')
        ->where('end_date', '<=', now()->addDays($days))
        ->where('end_date', '>', now())
        ->chunkById(200, function ($contracts) use ($webhooks, $engine) {
            foreach ($contracts as $c) {
                $webhooks->triggerWebhook('contract.expiring', [
                    'id' => $c->id,
                    'client_id' => $c->client_id,
                    'title' => $c->title,
                    'end_date' => $c->end_date?->toDateString(),
                    'days_until_expiration' => $c->daysUntilExpiration(),
                ], (int) $c->client_id);

                $engine->trigger('contract.expiring', [
                    'event' => 'contract.expiring',
                    'client_id' => $c->client_id,
                    'contract' => [
                        'id' => $c->id,
                        'client_id' => $c->client_id,
                        'title' => $c->title,
                        'end_date' => $c->end_date?->toDateString(),
                        'days_until_expiration' => $c->daysUntilExpiration(),
                    ],
                ]);
            }
        });
})->daily()->name('contract-expiring-webhooks');

// Automation schedule triggers + storage quota checks + cleanup
Schedule::call(function () {
    $engine = app(AutomationEngine::class);

    // Schedule-based triggers
    $engine->trigger('schedule.daily', ['event' => 'schedule.daily', 'timestamp' => now()->toISOString()]);
    if (now()->isMonday()) {
        $engine->trigger('schedule.weekly', ['event' => 'schedule.weekly', 'timestamp' => now()->toISOString()]);
    }
    if ((int) now()->format('j') === 1) {
        $engine->trigger('schedule.monthly', ['event' => 'schedule.monthly', 'timestamp' => now()->toISOString()]);
    }

    // Storage quota checks (per client against tier quota)
    $quotaByTierMb = (array) Setting::getValue('storage.quota_by_tier_mb', []);
    $quotaByTierBytes = collect($quotaByTierMb)->map(fn ($mb) => (int) $mb * 1024 * 1024)->all();

    $clientUsage = Client::query()
        ->leftJoin('storage_connections', function ($j) {
            $j->on('storage_connections.client_id', '=', 'clients.id')
                ->whereNull('storage_connections.deleted_at');
        })
        ->selectRaw('clients.id, clients.tier, SUM(COALESCE(storage_connections.storage_used,0)) as used')
        ->groupBy('clients.id', 'clients.tier')
        ->get();

    foreach ($clientUsage as $row) {
        $tier = (string) ($row->tier ?? 'standard');
        $quota = (int) ($quotaByTierBytes[$tier] ?? 0);
        $used = (int) ($row->used ?? 0);
        if ($quota <= 0) continue;
        $pct = ($used / $quota) * 100;
        if ($pct < 80) continue;

        $engine->trigger('storage.quota_reached', [
            'event' => 'storage.quota_reached',
            'client_id' => (int) $row->id,
            'client' => [
                'id' => (int) $row->id,
                'tier' => $tier,
            ],
            'storage' => [
                'used' => $used,
                'quota' => $quota,
                'pct' => (int) round($pct),
            ],
        ]);
    }

    // Storage cleanup: delete temp files older than 7 days (documents/tmp)
    try {
        $disk = \Illuminate\Support\Facades\Storage::disk('documents');
        $files = $disk->allFiles('tmp');
        foreach ($files as $f) {
            $ts = $disk->lastModified($f);
            if ($ts && $ts < now()->subDays(7)->getTimestamp()) {
                $disk->delete($f);
            }
        }
    } catch (\Throwable $e) {
        // ignore
    }
})->daily()->name('automation-schedules-and-storage');

// Storage sync scheduler (every 15 minutes by default)
Schedule::call(function () {
    $defaultFreq = (int) config('storage-providers.sync.frequency_minutes', 15);
    $now = now();

    $connections = StorageConnection::query()
        ->where('status', 'connected')
        ->where('auto_sync_enabled', true)
        ->get();

    foreach ($connections as $conn) {
        $freq = (int) ($conn->sync_frequency_minutes ?: $defaultFreq);
        $due = !$conn->last_synced_at || $conn->last_synced_at->lte($now->copy()->subMinutes($freq));
        if (!$due) {
            continue;
        }

        SyncStorageConnection::dispatch($conn->id, (int) config('storage-providers.sync.max_files_per_run', 500))
            ->onQueue('default');
    }
})->everyFifteenMinutes()->name('storage-sync');

// Scheduled report delivery (daily)
Schedule::call(function () {
    $due = ReportTemplate::query()
        ->where('is_active', true)
        ->whereIn('schedule', ['daily', 'weekly', 'monthly'])
        ->whereNotNull('next_send_at')
        ->where('next_send_at', '<=', now())
        ->get(['id']);

    foreach ($due as $tpl) {
        SendScheduledReport::dispatch($tpl->id)->onQueue('default');
    }
})->daily()->name('scheduled-reports');
