<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    /**
     * Handle incoming Stripe webhooks.
     */
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                $webhookSecret
            );
        } catch (SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        } catch (\Exception $e) {
            return response('Webhook error: '.$e->getMessage(), 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event->data->object);
                break;

            case 'charge.refunded':
                $this->handleChargeRefunded($event->data->object);
                break;

            default:
                // Unexpected event type
                return response('Unhandled event type', 200);
        }

        return response('Webhook handled', 200);
    }

    /**
     * Handle successful payment intent.
     */
    protected function handlePaymentIntentSucceeded($paymentIntent): void
    {
        $invoiceId = $paymentIntent->metadata->invoice_id ?? null;

        if (! $invoiceId) {
            return;
        }

        $invoice = Invoice::find($invoiceId);

        if (! $invoice) {
            return;
        }

        // Check if payment already recorded
        $existingPayment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($existingPayment) {
            if ($existingPayment->status !== 'succeeded') {
                $existingPayment->markAsSuccessful(
                    $paymentIntent->id,
                    $paymentIntent->latest_charge
                );
            }

            return;
        }

        // Create new payment record
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'amount' => $paymentIntent->amount / 100,
            'payment_method' => 'stripe',
            'stripe_payment_intent_id' => $paymentIntent->id,
            'stripe_charge_id' => $paymentIntent->latest_charge,
            'status' => 'succeeded',
            'processed_at' => now(),
        ]);

        // Check if invoice is fully paid
        if ($invoice->balance_due <= 0) {
            $invoice->markAsPaid();
        }

        ActivityLog::log(
            "Payment received for invoice: {$invoice->invoice_number}",
            $invoice,
            [
                'amount' => $payment->amount,
                'payment_intent_id' => $paymentIntent->id,
            ],
            'payment_received',
            'payments'
        );
    }

    /**
     * Handle failed payment intent.
     */
    protected function handlePaymentIntentFailed($paymentIntent): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($payment) {
            $payment->markAsFailed(
                $paymentIntent->last_payment_error->message ?? 'Payment failed'
            );
        }
    }

    /**
     * Handle refunded charge.
     */
    protected function handleChargeRefunded($charge): void
    {
        $payment = Payment::where('stripe_charge_id', $charge->id)->first();

        if ($payment) {
            $payment->update([
                'status' => 'refunded',
            ]);

            // Update invoice status
            $payment->invoice->update([
                'status' => 'refunded',
            ]);

            ActivityLog::log(
                "Payment refunded for invoice: {$payment->invoice->invoice_number}",
                $payment->invoice,
                [
                    'amount' => $charge->amount_refunded / 100,
                    'charge_id' => $charge->id,
                ],
                'payment_refunded',
                'payments'
            );
        }
    }
}
