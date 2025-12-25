<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\WebhookService;

class InvoiceWebhookObserver
{
    public function __construct(protected WebhookService $webhooks) {}

    public function created(Invoice $invoice): void
    {
        $this->webhooks->triggerWebhook('invoice.created', [
            'id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->status,
            'amount' => (float) $invoice->amount,
        ], (int) $invoice->client_id);

        if ($invoice->status === 'sent') {
            $this->webhooks->triggerWebhook('invoice.sent', [
                'id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'invoice_number' => $invoice->invoice_number,
            ], (int) $invoice->client_id);
        }
    }

    public function updated(Invoice $invoice): void
    {
        if ($invoice->wasChanged('status') && $invoice->status === 'sent') {
            $this->webhooks->triggerWebhook('invoice.sent', [
                'id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'invoice_number' => $invoice->invoice_number,
            ], (int) $invoice->client_id);
        }

        if ($invoice->wasChanged('status') && $invoice->status === 'paid') {
            $this->webhooks->triggerWebhook('invoice.paid', [
                'id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'invoice_number' => $invoice->invoice_number,
                'paid_at' => $invoice->paid_at?->toISOString(),
            ], (int) $invoice->client_id);
        }
    }
}

