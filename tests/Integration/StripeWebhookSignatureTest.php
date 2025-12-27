<?php

namespace Tests\Integration;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeWebhookSignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_stripe_webhook_with_valid_signature_records_payment_and_marks_invoice_paid(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test_123');

        $client = Client::factory()->create();
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => 'sent',
            'amount' => 50.00,
        ]);

        $payload = json_encode([
            'id' => 'evt_test_123',
            'object' => 'event',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'object' => 'payment_intent',
                    'amount' => 5000,
                    'latest_charge' => 'ch_test_123',
                    'metadata' => [
                        'invoice_id' => (string) $invoice->id,
                    ],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES);

        $timestamp = time();
        $signedPayload = $timestamp.'.'.$payload;
        $sig = hash_hmac('sha256', $signedPayload, (string) config('services.stripe.webhook_secret'));
        $header = "t={$timestamp},v1={$sig}";

        $this->call('POST', '/webhooks/stripe', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $header,
        ], $payload)->assertOk();

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'stripe_payment_intent_id' => 'pi_test_123',
            'stripe_charge_id' => 'ch_test_123',
            'status' => 'succeeded',
        ]);

        $invoice->refresh();
        $this->assertSame('paid', $invoice->status);
    }
}
