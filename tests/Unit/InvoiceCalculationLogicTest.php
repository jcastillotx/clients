<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceCalculationLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_calculates_subtotal_tax_discount_and_never_negative(): void
    {
        $client = Client::factory()->create();

        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'tax_rate' => 10, // %
            'discount' => 5,
            'subtotal' => 0,
            'tax_amount' => 0,
            'amount' => 0,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'quantity' => 2,
            'unit_price' => 20,
            'total' => 40,
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'quantity' => 1,
            'unit_price' => 10,
            'total' => 10,
        ]);

        $invoice->calculateTotals();
        $invoice->refresh();

        $this->assertEquals(50.00, (float) $invoice->subtotal);
        $this->assertEquals(5.00, (float) $invoice->tax_amount);
        $this->assertEquals(50.00, (float) $invoice->subtotal);
        $this->assertEquals(50.00 + 5.00 - 5.00, (float) $invoice->amount);
    }

    public function test_invoice_balance_due_reflects_successful_payments_only(): void
    {
        $client = Client::factory()->create();

        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'amount' => 100,
            'status' => 'sent',
        ]);

        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'amount' => 30,
            'status' => 'succeeded',
        ]);
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'amount' => 50,
            'status' => 'failed',
        ]);

        $invoice->refresh();

        $this->assertEquals(30.00, (float) $invoice->total_paid);
        $this->assertEquals(70.00, (float) $invoice->balance_due);
    }
}
