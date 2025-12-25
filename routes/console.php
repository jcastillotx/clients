<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Invoice;
use App\Models\StorageConnection;
use App\Models\User;
use App\Mail\InvoiceReminderMail;
use App\Jobs\SyncStorageConnection;
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
    Invoice::where('status', 'sent')
        ->where('due_date', '<', now())
        ->update(['status' => 'overdue']);
})->daily()->name('check-overdue-invoices');

// Invoice reminders (daily)
Schedule::call(function () {
    $today = now()->startOfDay();

    // 7 days before due date (sent invoices only)
    $dueSoonDate = $today->copy()->addDays(7)->toDateString();
    $dueSoon = Invoice::query()
        ->where('status', 'sent')
        ->whereDate('due_date', $dueSoonDate)
        ->whereNull('reminded_due_7_at')
        ->with(['client'])
        ->get();

    foreach ($dueSoon as $invoice) {
        $recipients = User::query()
            ->where('client_id', $invoice->client_id)
            ->role(['client'])
            ->get();

        foreach ($recipients as $user) {
            if (!$user->email) continue;
            Mail::to($user->email)->queue(new InvoiceReminderMail($invoice, 'due_soon'));
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
        $recipients = User::query()
            ->where('client_id', $invoice->client_id)
            ->role(['client'])
            ->get();

        foreach ($recipients as $user) {
            if (!$user->email) continue;
            Mail::to($user->email)->queue(new InvoiceReminderMail($invoice, 'overdue'));
        }

        $invoice->update(['reminded_overdue_3_at' => now()]);
    }
})->daily()->name('invoice-reminders');

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
