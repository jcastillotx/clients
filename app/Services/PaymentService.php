<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;

/**
 * Centralized payment recording logic.
 *
 * Controllers/webhooks can use this service to keep invoice/payment state
 * transitions consistent.
 */
class PaymentService
{
    /**
     * Record a successful Stripe payment for an invoice.
     */
    public function recordSuccessfulStripePayment(
        Invoice $invoice,
        string $paymentIntentId,
        ?string $chargeId = null,
        ?float $amount = null
    ): Payment {
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'amount' => $amount ?? (float) $invoice->amount,
            'payment_method' => 'stripe',
            'stripe_payment_intent_id' => $paymentIntentId,
            'status' => 'processing',
        ]);

        $payment->markAsSuccessful($paymentIntentId, $chargeId);

        return $payment->fresh();
    }
}

