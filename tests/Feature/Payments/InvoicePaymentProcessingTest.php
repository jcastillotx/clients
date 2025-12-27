<?php

namespace Tests\Feature\Payments;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class InvoicePaymentProcessingTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function test_client_can_process_successful_payment_intent_and_invoice_is_marked_paid(): void
    {
        $this->seed(RolePermissionSeeder::class);

        config()->set('services.stripe.secret', 'sk_test_dummy');
        config()->set('services.stripe.key', 'pk_test_dummy');

        // Mock Stripe\PaymentIntent::retrieve (static) without doing network I/O.
        $pi = new \stdClass;
        $pi->id = 'pi_test_123';
        $pi->status = 'succeeded';
        $pi->latest_charge = 'ch_test_123';

        Mockery::mock('alias:Stripe\\PaymentIntent')
            ->shouldReceive('retrieve')
            ->once()
            ->with('pi_test_123')
            ->andReturn($pi);

        $client = Client::factory()->create();
        $user = User::factory()->forClient($client)->create();
        $user->assignRole('client');

        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => 'sent',
            'discount' => 0,
            'tax_rate' => 0,
        ]);
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'quantity' => 1,
            'unit_price' => 50,
            'total' => 50,
        ]);
        $invoice->calculateTotals();
        $invoice->refresh();

        $this->actingAs($user)
            ->post(route('payments.process', $invoice), [
                'payment_intent_id' => 'pi_test_123',
            ])
            ->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'stripe_payment_intent_id' => 'pi_test_123',
            'stripe_charge_id' => 'ch_test_123',
            'status' => 'succeeded',
        ]);

        $payment = Payment::query()->where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertTrue($payment->isSuccessful());
    }
}
