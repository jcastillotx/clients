<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $clients = Client::query()->take(3)->get();
        if ($clients->isEmpty()) {
            $clients = Client::factory()->count(3)->active()->create();
        }

        // Create 5 invoices total across clients
        $invoices = Invoice::factory()
            ->count(5)
            ->make()
            ->each(function (Invoice $invoice) use ($clients) {
                $client = $clients->random();
                $invoice->client_id = $client->id;
                $invoice->save();

                $itemsCount = random_int(1, 4);
                for ($i = 0; $i < $itemsCount; $i++) {
                    InvoiceItem::factory()->create([
                        'invoice_id' => $invoice->id,
                        'sort_order' => $i,
                    ]);
                }

                // Ensure totals are updated
                $invoice->refresh();

                // Add payment records for paid invoices
                if ($invoice->status === 'paid') {
                    Payment::factory()->create([
                        'invoice_id' => $invoice->id,
                        'client_id' => $invoice->client_id,
                        'amount' => (float) $invoice->amount,
                        'status' => 'succeeded',
                        'processed_at' => $invoice->paid_at ?? now(),
                    ]);
                }
            });
    }
}

