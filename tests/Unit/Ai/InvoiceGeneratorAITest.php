<?php

namespace Tests\Unit\Ai;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\AI\AISafetyService;
use App\Services\AI\InvoiceGeneratorAI;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InvoiceGeneratorAITest extends TestCase
{
    use RefreshDatabase;

    public function test_predict_payment_uses_client_average_days_to_pay(): void
    {
        $client = Client::factory()->create();

        $inv1 = Invoice::withoutEvents(fn () => Invoice::query()->create([
            'client_id' => $client->id,
            'request_id' => null,
            'invoice_number' => 'INV-TEST-0001',
            'subtotal' => 0,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'discount' => 0,
            'amount' => 0,
            'issue_date' => '2025-01-01',
            'due_date' => '2025-01-31',
            'paid_at' => Carbon::parse('2025-01-11'),
            'status' => 'paid',
            'notes' => null,
            'terms' => null,
            'pdf_path' => null,
            'template' => 'classic',
        ]));
        $inv2 = Invoice::withoutEvents(fn () => Invoice::query()->create([
            'client_id' => $client->id,
            'request_id' => null,
            'invoice_number' => 'INV-TEST-0002',
            'subtotal' => 0,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'discount' => 0,
            'amount' => 0,
            'issue_date' => '2025-02-01',
            'due_date' => '2025-03-03',
            'paid_at' => Carbon::parse('2025-02-21'),
            'status' => 'paid',
            'notes' => null,
            'terms' => null,
            'pdf_path' => null,
            'template' => 'classic',
        ]));

        $this->assertNotNull($inv1->paid_at);
        $this->assertNotNull($inv2->paid_at);

        $invoice = Invoice::withoutEvents(fn () => Invoice::query()->create([
            'client_id' => $client->id,
            'request_id' => null,
            'invoice_number' => 'INV-TEST-0003',
            'subtotal' => 0,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'discount' => 0,
            'amount' => 0,
            'issue_date' => '2025-03-01',
            'due_date' => '2025-03-15',
            'paid_at' => null,
            'status' => 'sent',
            'notes' => null,
            'terms' => null,
            'pdf_path' => null,
            'template' => 'classic',
        ]));
        $invoice->refresh();
        $this->assertSame('2025-03-01', $invoice->issue_date?->toDateString());

        $safety = \Mockery::mock(AISafetyService::class);
        $svc = new InvoiceGeneratorAI($safety);

        $out = $svc->predictPayment($invoice, $client);
        $this->assertSame(15, (int) ($out['_history']['avg_days_to_pay'] ?? 0));
        $this->assertSame('2025-03-16', $out['predicted_payment_date']); // avg(10,20)=15 days
        $this->assertArrayHasKey('recommended_reminders', $out);
    }

    public function test_review_invoice_fallback_flags_math_mismatch_when_ai_unavailable(): void
    {
        $client = Client::factory()->create();
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'tax_rate' => 0,
            'discount' => 0,
            'amount' => 90,
        ]);

        InvoiceItem::factory()->for($invoice)->create([
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);

        // Some model hooks may recalculate totals; force a mismatch afterwards.
        $invoice->refresh()->load('items');
        $invoice->update(['amount' => 90]);
        $invoice->refresh()->load('items');

        $safety = \Mockery::mock(AISafetyService::class);
        $safety->shouldReceive('safeChat')->andThrow(new \RuntimeException('AI not configured'));

        $svc = new InvoiceGeneratorAI($safety);
        $out = $svc->reviewInvoice($invoice);

        $this->assertFalse((bool) ($out['math_ok'] ?? true));
        $this->assertContains('math_mismatch', (array) ($out['flags'] ?? []));
    }
}
