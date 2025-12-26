<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Invoice;
use App\Services\AdminReports\ReportScheduleRunner;

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

// Send scheduled admin reports (requires mail configuration)
Schedule::call(function () {
    app(ReportScheduleRunner::class)->runDueSchedules();
})->everyFiveMinutes()->name('send-scheduled-admin-reports');
