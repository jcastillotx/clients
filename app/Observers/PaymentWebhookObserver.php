<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\AutomationEngine;
use App\Services\WebhookService;

class PaymentWebhookObserver
{
    public function __construct(
        protected WebhookService $webhooks,
        protected AutomationEngine $automations,
    ) {}

    public function updated(Payment $payment): void
    {
        if (!$payment->wasChanged('status')) {
            return;
        }

        if ($payment->status === 'succeeded') {
            $payload = [
                'event' => 'payment.received',
                'client_id' => $payment->client_id,
                'payment' => [
                    'id' => $payment->id,
                    'client_id' => $payment->client_id,
                    'invoice_id' => $payment->invoice_id,
                    'amount' => (float) $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'status' => $payment->status,
                ],
            ];
            $this->webhooks->triggerWebhook('payment.received', [
                'id' => $payment->id,
                'client_id' => $payment->client_id,
                'invoice_id' => $payment->invoice_id,
                'amount' => (float) $payment->amount,
                'payment_method' => $payment->payment_method,
            ], (int) $payment->client_id);
            $this->automations->trigger('payment.received', $payload);
        }

        if ($payment->status === 'failed') {
            $payload = [
                'event' => 'payment.failed',
                'client_id' => $payment->client_id,
                'payment' => [
                    'id' => $payment->id,
                    'client_id' => $payment->client_id,
                    'invoice_id' => $payment->invoice_id,
                    'amount' => (float) $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'status' => $payment->status,
                    'failure_reason' => $payment->failure_reason,
                ],
            ];
            $this->webhooks->triggerWebhook('payment.failed', [
                'id' => $payment->id,
                'client_id' => $payment->client_id,
                'invoice_id' => $payment->invoice_id,
                'amount' => (float) $payment->amount,
                'payment_method' => $payment->payment_method,
                'reason' => $payment->failure_reason,
            ], (int) $payment->client_id);
            $this->automations->trigger('payment.failed', $payload);
        }
    }
}

