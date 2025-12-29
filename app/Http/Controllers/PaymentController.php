<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class PaymentController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(SettingsService $settings)
    {
        // First try environment variable, then fall back to database settings
        $stripeSecret = config('services.stripe.secret');
        
        if (empty($stripeSecret)) {
            $mode = $settings->get('payment.stripe.mode', 'test');
            $stripeSecret = $mode === 'live'
                ? $settings->get('payment.stripe.live_secret')
                : $settings->get('payment.stripe.test_secret');
        }
        
        Stripe::setApiKey($stripeSecret);
    }

    /**
     * Show payment page for an invoice.
     */
    public function show(Invoice $invoice, SettingsService $settings): View
    {
        $this->authorizeClientAccess($invoice);

        if (! $invoice->canBePaid()) {
            abort(400, 'This invoice cannot be paid.');
        }

        // Create a payment intent
        $paymentIntent = $this->createPaymentIntent($invoice);

        // Get the Stripe public key from env or database settings
        $stripeKey = config('services.stripe.key');
        if (empty($stripeKey)) {
            $mode = $settings->get('payment.stripe.mode', 'test');
            $stripeKey = $mode === 'live'
                ? $settings->get('payment.stripe.live_public')
                : $settings->get('payment.stripe.test_public');
        }

        return view('payments.show', [
            'invoice' => $invoice,
            'clientSecret' => $paymentIntent->client_secret,
            'stripeKey' => $stripeKey,
        ]);
    }

    /**
     * Process the payment.
     */
    public function process(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorizeClientAccess($invoice);

        if (! $invoice->canBePaid()) {
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
            return back()->with('error', 'Payment failed: '.$e->getMessage());
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
