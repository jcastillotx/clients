<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_as_successful_marks_invoice_paid_when_fully_paid(): void
    {
        $client = Client::factory()->create();
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'amount' => 50,
            'status' => 'sent',
        ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'amount' => 50,
            'status' => 'processing',
        ]);

        $payment->markAsSuccessful('pi_test', 'ch_test');

        $invoice->refresh();
        $payment->refresh();

        $this->assertSame('succeeded', $payment->status);
        $this->assertSame('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
    }
}

