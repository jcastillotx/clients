<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\WebhookService;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        $this->fireStatus($payment);
    }

    public function updated(Payment $payment): void
    {
        if ($payment->wasChanged('status')) {
            $this->fireStatus($payment);
        }
    }

    protected function fireStatus(Payment $payment): void
    {
        $clientId = (int) $payment->client_id;

        if ($payment->status === 'succeeded') {
            app(WebhookService::class)->triggerWebhook('payment.received', [
                'id' => $payment->id,
                'client_id' => $payment->client_id,
                'invoice_id' => $payment->invoice_id,
                'amount' => (float) $payment->amount,
                'payment_method' => $payment->payment_method,
                'transaction_id' => $payment->transaction_id,
                'stripe_payment_intent_id' => $payment->stripe_payment_intent_id,
                'status' => $payment->status,
                'processed_at' => optional($payment->processed_at)->toISOString(),
            ], $clientId);
        }

        if ($payment->status === 'failed') {
            app(WebhookService::class)->triggerWebhook('payment.failed', [
                'id' => $payment->id,
                'client_id' => $payment->client_id,
                'invoice_id' => $payment->invoice_id,
                'amount' => (float) $payment->amount,
                'payment_method' => $payment->payment_method,
                'transaction_id' => $payment->transaction_id,
                'stripe_payment_intent_id' => $payment->stripe_payment_intent_id,
                'status' => $payment->status,
                'failure_reason' => $payment->failure_reason,
                'processed_at' => optional($payment->processed_at)->toISOString(),
            ], $clientId);
        }
    }
}

