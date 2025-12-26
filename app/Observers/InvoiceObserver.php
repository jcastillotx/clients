<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\WebhookService;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        app(WebhookService::class)->triggerWebhook('invoice.created', [
            'id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->status,
            'amount' => (float) $invoice->amount,
            'issue_date' => optional($invoice->issue_date)->toDateString(),
            'due_date' => optional($invoice->due_date)->toDateString(),
            'created_at' => optional($invoice->created_at)->toISOString(),
        ], (int) $invoice->client_id);
    }

    public function updated(Invoice $invoice): void
    {
        if ($invoice->wasChanged('status') && $invoice->status === 'sent') {
            app(WebhookService::class)->triggerWebhook('invoice.sent', [
                'id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'amount' => (float) $invoice->amount,
            ], (int) $invoice->client_id);
        }

        if ($invoice->wasChanged('status') && $invoice->status === 'paid') {
            app(WebhookService::class)->triggerWebhook('invoice.paid', [
                'id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'amount' => (float) $invoice->amount,
                'paid_at' => optional($invoice->paid_at)->toISOString(),
            ], (int) $invoice->client_id);
        }
    }
}

