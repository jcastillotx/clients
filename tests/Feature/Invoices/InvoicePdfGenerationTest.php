<?php

namespace Tests\Feature\Invoices;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class InvoicePdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_download_generates_pdf_when_missing(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('invoices');

        // Mock DomPDF facade so the test doesn't depend on font rendering/binaries.
        $pdf = Mockery::mock(DomPdfWrapper::class);
        $pdf->shouldReceive('output')->andReturn('%PDF-FAKE%');

        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($pdf);

        $client = Client::factory()->create();
        $user = User::factory()->forClient($client)->create();
        $user->assignRole('client');

        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => 'sent',
            'pdf_path' => null,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);

        $invoice->calculateTotals();
        $invoice->refresh();

        $resp = $this->actingAs($user)->get(route('invoices.download', $invoice));
        $resp->assertOk();

        $invoice->refresh();
        $this->assertNotNull($invoice->pdf_path);
        Storage::disk('invoices')->assertExists($invoice->pdf_path);
    }
}

