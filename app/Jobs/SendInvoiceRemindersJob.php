<?php

namespace App\Jobs;

use App\Mail\InvoiceReminderMail;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInvoiceRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Days before due date to send "due soon" reminder.
     */
    protected int $dueSoonDays = 7;

    /**
     * Days between overdue reminders.
     */
    protected int $overdueReminderIntervalDays = 3;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->sendDueSoonReminders();
        $this->sendOverdueReminders();
    }

    /**
     * Send reminders for invoices due soon (7 days before due date).
     */
    protected function sendDueSoonReminders(): void
    {
        // Find invoices due within 7 days that haven't received this reminder
        $targetDate = now()->addDays($this->dueSoonDays)->toDateString();

        $invoices = Invoice::query()
            ->where('status', 'sent')
            ->whereDate('due_date', '<=', $targetDate)
            ->whereDate('due_date', '>', now())
            ->whereNull('reminded_due_7_at')
            ->with('client')
            ->get();

        foreach ($invoices as $invoice) {
            $this->sendReminder($invoice, 'due_soon', 'reminded_due_7_at');
        }

        if ($invoices->count() > 0) {
            Log::info('Sent due soon invoice reminders', ['count' => $invoices->count()]);
        }
    }

    /**
     * Send reminders for overdue invoices.
     */
    protected function sendOverdueReminders(): void
    {
        // Find overdue invoices that haven't been reminded or were reminded more than 3 days ago
        $invoices = Invoice::query()
            ->where('status', 'overdue')
            ->where(function ($q) {
                $q->whereNull('reminded_overdue_3_at')
                    ->orWhere('reminded_overdue_3_at', '<', now()->subDays($this->overdueReminderIntervalDays));
            })
            ->with('client')
            ->get();

        foreach ($invoices as $invoice) {
            $this->sendReminder($invoice, 'overdue', 'reminded_overdue_3_at');
        }

        if ($invoices->count() > 0) {
            Log::info('Sent overdue invoice reminders', ['count' => $invoices->count()]);
        }
    }

    /**
     * Send a reminder for a specific invoice.
     */
    protected function sendReminder(Invoice $invoice, string $kind, string $timestampField): void
    {
        $client = $invoice->client;

        if (! $client || ! $client->email) {
            Log::warning('Cannot send invoice reminder - no client email', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ]);

            return;
        }

        try {
            Mail::to($client->email)->send(new InvoiceReminderMail($invoice, $kind));

            $invoice->update([
                $timestampField => now(),
            ]);

            Log::info('Invoice reminder sent', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'kind' => $kind,
                'client_email' => $client->email,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send invoice reminder', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
