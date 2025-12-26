<?php

namespace Tests\Browser;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Concerns\ProvidesBrowser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\DuskTestCase;

class ClientFullJourneyTest extends DuskTestCase
{
    use DatabaseMigrations;
    use ProvidesBrowser;

    public function test_client_journey_login_create_request_upload_file_pay_invoice(): void
    {
        if (!env('RUN_DUSK_TESTS')) {
            $this->markTestSkipped('Set RUN_DUSK_TESTS=1 to run browser tests.');
        }

        $this->seed(RolePermissionSeeder::class);

        $client = Client::factory()->create();
        $user = User::factory()->forClient($client)->create([
            'email' => 'client@example.com',
        ]);
        $user->assignRole('client');

        // Create an invoice the client can pay.
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => 'sent',
            'tax_rate' => 0,
            'discount' => 0,
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'quantity' => 1,
            'unit_price' => 10,
            'total' => 10,
        ]);
        $invoice->calculateTotals();

        $this->browse(function (Browser $browser) use ($invoice) {
            $browser->visit('/login')
                ->type('email', 'client@example.com')
                ->type('password', 'password')
                ->press('Log in')
                ->assertPathIs('/dashboard');

            // Create request (Livewire form). Selectors are intentionally simple; adjust if UI changes.
            $browser->visit('/requests/create')
                ->type('input[type=text]', 'Need help with a new landing page')
                ->type('textarea', 'Please build a new landing page for our campaign. Here are the details...')
                ->attach('input[type=file]', __DIR__ . '/fixtures/sample.pdf')
                ->press('Save draft')
                ->assertSee('Request');

            // Pay invoice (Stripe Elements requires browser-side confirmation; this is a skeleton).
            $browser->visit("/invoices/{$invoice->id}")
                ->clickLink('Pay')
                ->assertPathBeginsWith("/invoices/{$invoice->id}/pay");

            // TODO: Use Stripe test mode + Stripe.js to confirm payment intent.
            // After confirmation, the app redirects back to the invoice page with success.
        });
    }
}

