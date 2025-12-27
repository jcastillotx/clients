<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Invoice;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_service_records_success_and_marks_invoice_paid(): void
    {
        $client = Client::factory()->create();
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'amount' => 25,
            'status' => 'sent',
        ]);

        $payment = app(PaymentService::class)->recordSuccessfulStripePayment(
            invoice: $invoice,
            paymentIntentId: 'pi_test_999',
            chargeId: 'ch_test_999',
            amount: 25
        );

        $this->assertSame('succeeded', $payment->status);
        $this->assertSame('pi_test_999', $payment->transaction_id);
        $this->assertSame('ch_test_999', $payment->stripe_charge_id);

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
    }
}

