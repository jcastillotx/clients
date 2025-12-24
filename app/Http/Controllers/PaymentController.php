<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;

class PaymentController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Show payment page for an invoice.
     */
    public function show(Invoice $invoice): View
    {
        $this->authorizeClientAccess($invoice);

        if (!$invoice->canBePaid()) {
            abort(400, 'This invoice cannot be paid.');
        }

        // Create a payment intent
        $paymentIntent = $this->createPaymentIntent($invoice);

        return view('payments.show', [
            'invoice' => $invoice,
            'clientSecret' => $paymentIntent->client_secret,
            'stripeKey' => config('services.stripe.key'),
        ]);
    }

    /**
     * Process the payment.
     */
    public function process(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorizeClientAccess($invoice);

        if (!$invoice->canBePaid()) {
            return back()->with('error', 'This invoice cannot be paid.');
        }

        $validated = $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        try {
            $paymentIntent = PaymentIntent::retrieve($validated['payment_intent_id']);

            if ($paymentIntent->status === 'succeeded') {
                // Create payment record
                $payment = Payment::create([
                    'invoice_id' => $invoice->id,
                    'client_id' => $invoice->client_id,
                    'amount' => $invoice->amount,
                    'payment_method' => 'stripe',
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'status' => 'processing',
                ]);

                $payment->markAsSuccessful(
                    $paymentIntent->id,
                    $paymentIntent->latest_charge
                );

                ActivityLog::log(
                    "Paid invoice: {$invoice->invoice_number}",
                    $invoice,
                    [
                        'amount' => $invoice->amount,
                        'payment_method' => 'stripe',
                    ],
                    'paid',
                    'payments'
                );

                return redirect()
                    ->route('invoices.show', $invoice)
                    ->with('success', 'Payment successful! Thank you.');
            }

            return back()->with('error', 'Payment was not completed. Please try again.');

        } catch (ApiErrorException $e) {
            return back()->with('error', 'Payment failed: ' . $e->getMessage());
        }
    }

    /**
     * Create a Stripe payment intent.
     */
    protected function createPaymentIntent(Invoice $invoice): PaymentIntent
    {
        $client = $invoice->client;

        return PaymentIntent::create([
            'amount' => (int) ($invoice->amount * 100), // Convert to cents
            'currency' => 'usd',
            'customer' => $client->stripe_customer_id,
            'metadata' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'client_id' => $client->id,
            ],
            'description' => "Payment for Invoice {$invoice->invoice_number}",
        ]);
    }

    /**
     * Authorize that the current user can access this invoice.
     */
    protected function authorizeClientAccess(Invoice $invoice): void
    {
        $user = auth()->user();

        if ($user->isClient() && $invoice->client_id !== $user->client_id) {
            abort(403, 'You do not have access to this invoice.');
        }
    }
}
