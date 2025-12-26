<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\AutomationEngine;
use App\Services\WebhookService;

class InvoiceWebhookObserver
{
    public function __construct(
        protected WebhookService $webhooks,
        protected AutomationEngine $automations,
    ) {}

    public function created(Invoice $invoice): void
    {
        $basePayload = [
            'event' => 'invoice.created',
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

        $this->webhooks->triggerWebhook('invoice.created', [
            'id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->status,
            'amount' => (float) $invoice->amount,
        ], (int) $invoice->client_id);
        $this->automations->trigger('invoice.created', $basePayload);

        if ($invoice->status === 'sent') {
            $sentPayload = $basePayload;
            $sentPayload['event'] = 'invoice.sent';
            $this->webhooks->triggerWebhook('invoice.sent', [
                'id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'invoice_number' => $invoice->invoice_number,
            ], (int) $invoice->client_id);
            $this->automations->trigger('invoice.sent', $sentPayload);
        }
    }

    public function updated(Invoice $invoice): void
    {
        if ($invoice->wasChanged('status') && $invoice->status === 'sent') {
            $payload = [
                'event' => 'invoice.sent',
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
            $this->webhooks->triggerWebhook('invoice.sent', [
                'id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'invoice_number' => $invoice->invoice_number,
            ], (int) $invoice->client_id);
            $this->automations->trigger('invoice.sent', $payload);
        }

        if ($invoice->wasChanged('status') && $invoice->status === 'paid') {
            $payload = [
                'event' => 'invoice.paid',
                'client_id' => $invoice->client_id,
                'invoice' => [
                    'id' => $invoice->id,
                    'client_id' => $invoice->client_id,
                    'invoice_number' => $invoice->invoice_number,
                    'status' => $invoice->status,
                    'amount' => (float) $invoice->amount,
                    'paid_at' => $invoice->paid_at?->toISOString(),
                ],
            ];
            $this->webhooks->triggerWebhook('invoice.paid', [
                'id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'invoice_number' => $invoice->invoice_number,
                'paid_at' => $invoice->paid_at?->toISOString(),
            ], (int) $invoice->client_id);
            $this->automations->trigger('invoice.paid', $payload);
        }
    }
}

