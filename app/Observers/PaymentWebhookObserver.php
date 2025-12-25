<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\WebhookService;

class PaymentWebhookObserver
{
    public function __construct(protected WebhookService $webhooks) {}

    public function updated(Payment $payment): void
    {
        if (!$payment->wasChanged('status')) {
            return;
        }

        if ($payment->status === 'succeeded') {
            $this->webhooks->triggerWebhook('payment.received', [
                'id' => $payment->id,
                'client_id' => $payment->client_id,
                'invoice_id' => $payment->invoice_id,
                'amount' => (float) $payment->amount,
                'payment_method' => $payment->payment_method,
            ], (int) $payment->client_id);
        }

        if ($payment->status === 'failed') {
            $this->webhooks->triggerWebhook('payment.failed', [
                'id' => $payment->id,
                'client_id' => $payment->client_id,
                'invoice_id' => $payment->invoice_id,
                'amount' => (float) $payment->amount,
                'payment_method' => $payment->payment_method,
                'reason' => $payment->failure_reason,
            ], (int) $payment->client_id);
        }
    }
}

