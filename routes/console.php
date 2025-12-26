<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Invoice;
use App\Models\Contract;
use App\Services\AdminReports\ReportScheduleRunner;
use App\Services\Storage\StorageSyncScheduler;
use App\Services\WebhookService;

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

// Trigger contract expiring webhooks daily (simple daily reminder until expiration)
Schedule::call(function () {
    $contracts = Contract::expiringSoon(30)->get();
    foreach ($contracts as $c) {
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

// Send scheduled admin reports (requires mail configuration)
Schedule::call(function () {
    app(ReportScheduleRunner::class)->runDueSchedules();
})->everyFiveMinutes()->name('send-scheduled-admin-reports');

// Auto-sync connected storage providers (requires queue worker)
Schedule::call(function () {
    app(StorageSyncScheduler::class)->dispatchDue();
})->everyFiveMinutes()->name('storage-auto-sync');
