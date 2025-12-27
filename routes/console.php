<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Invoice;
use App\Models\Contract;
use App\Models\StorageConnection;
use App\Services\AdminReports\ReportScheduleRunner;
use App\Services\Storage\StorageSyncScheduler;
use App\Services\AutomationEngine;
use App\Services\WebhookService;
use App\Services\AI\RequestEmbeddingService;
use App\Models\Request as ServiceRequest;
use App\Jobs\Analytics\UpdateClientHealthScoresJob;
use App\Jobs\Analytics\GenerateWeeklyTrendReportJob;
use App\Jobs\Analytics\GenerateMonthlyRevenueForecastJob;
use App\Jobs\Analytics\GenerateQuarterlyBusinessIntelligenceReportJob;

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
    Invoice::where('status', 'sent')
        ->where('due_date', '<', now())
        ->update(['status' => 'overdue']);
})->daily()->name('check-overdue-invoices');

// Scheduled automation triggers
Schedule::call(function () {
    app(AutomationEngine::class)->run('schedule.daily', ['meta' => ['date' => now()->toDateString()]]);
})->daily()->name('automation-schedule-daily');

Schedule::call(function () {
    app(AutomationEngine::class)->run('schedule.weekly', ['meta' => ['date' => now()->toDateString()]]);
})->weekly()->name('automation-schedule-weekly');

Schedule::call(function () {
    app(AutomationEngine::class)->run('schedule.monthly', ['meta' => ['date' => now()->toDateString()]]);
})->monthly()->name('automation-schedule-monthly');

// Trigger contract expiring webhooks daily (simple daily reminder until expiration)
Schedule::call(function () {
    $contracts = Contract::expiringSoon(30)->get();
    foreach ($contracts as $c) {
        // Automation trigger
        app(AutomationEngine::class)->run('contract.expiring', [
            'contract' => $c->toArray(),
            'client' => $c->client?->toArray(),
        ], (int) $c->client_id);

        app(WebhookService::class)->triggerWebhook('contract.expiring', [
            'id' => $c->id,
            'client_id' => $c->client_id,
            'contract_number' => $c->contract_number,
            'title' => $c->title,
            'status' => $c->status,
            'end_date' => optional($c->end_date)->toDateString(),
            'days_until_expiration' => $c->days_until_expiration,
        ], (int) $c->client_id);
    }
})->daily()->name('contract-expiring-webhooks');

// Invoice due date approaching (7 days) automation trigger
Schedule::call(function () {
    $cutoff = now()->addDays(7)->toDateString();
    $invoices = Invoice::query()
        ->whereIn('status', ['sent', 'overdue'])
        ->whereDate('due_date', $cutoff)
        ->get();

    foreach ($invoices as $inv) {
        app(AutomationEngine::class)->run('invoice.due_approaching', [
            'invoice' => $inv->toArray(),
            'client' => $inv->client?->toArray(),
            'meta' => ['days_before' => 7],
        ], (int) $inv->client_id);
    }
})->daily()->name('automation-invoice-due-approaching');

// Storage quota reached automation trigger (>=80%)
Schedule::call(function () {
    $connections = StorageConnection::query()
        ->where('status', 'active')
        ->whereNotNull('quota_bytes')
        ->get();

    foreach ($connections as $conn) {
        $quota = (int) $conn->quota_bytes;
        if ($quota <= 0) {
            continue;
        }
        $used = (int) $conn->used_bytes;
        $percent = (int) floor(($used / $quota) * 100);
        if ($percent >= 80) {
            app(AutomationEngine::class)->run('storage.quota_reached', [
                'storage' => [
                    'connection_id' => $conn->id,
                    'provider' => $conn->provider,
                    'name' => $conn->name,
                    'used_bytes' => $used,
                    'quota_bytes' => $quota,
                    'percent' => $percent,
                ],
                'client' => $conn->client?->toArray(),
            ], (int) $conn->client_id);
        }
    }
})->daily()->name('automation-storage-quota-reached');

// Send scheduled admin reports (requires mail configuration)
Schedule::call(function () {
    app(ReportScheduleRunner::class)->runDueSchedules();
})->everyFiveMinutes()->name('send-scheduled-admin-reports');

// Auto-sync connected storage providers (requires queue worker)
Schedule::call(function () {
    app(StorageSyncScheduler::class)->dispatchDue();
})->everyFiveMinutes()->name('storage-auto-sync');

// AI analytics: update client health scores daily
Schedule::call(function () {
    UpdateClientHealthScoresJob::dispatch();
})->daily()->name('ai-analytics-client-health-daily');

// AI analytics: weekly trends report
Schedule::call(function () {
    GenerateWeeklyTrendReportJob::dispatch();
})->weekly()->name('ai-analytics-weekly-trends');

// AI analytics: monthly forecast report
Schedule::call(function () {
    GenerateMonthlyRevenueForecastJob::dispatch();
})->monthly()->name('ai-analytics-monthly-forecast');

// AI analytics: quarterly business intelligence report
Schedule::call(function () {
    GenerateQuarterlyBusinessIntelligenceReportJob::dispatch();
})->quarterly()->name('ai-analytics-quarterly-bi');

/*
|--------------------------------------------------------------------------
| AI / Embeddings Utilities
|--------------------------------------------------------------------------
*/

Artisan::command('ai:embeddings:backfill {--limit=200} {--provider=openai} {--model=text-embedding-3-small}', function () {
    $limit = (int) $this->option('limit');
    $provider = (string) $this->option('provider');
    $model = (string) $this->option('model');

    $svc = app(RequestEmbeddingService::class);

    $count = 0;
    $skipped = 0;
    $q = ServiceRequest::query()->orderByDesc('id')->limit(max(1, $limit));
    foreach ($q->cursor() as $req) {
        $row = $svc->upsertRequestEmbedding($req, [
            'provider' => $provider,
            'model' => $model,
            'timeout' => 45,
        ]);
        if ($row) {
            $count++;
            $this->line("Embedded request #{$req->id}");
        } else {
            $skipped++;
        }
    }

    $this->info("Done. Embedded={$count}, skipped={$skipped}.");
})->purpose('Backfill semantic embeddings for past requests');
