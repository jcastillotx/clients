<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Tests\TestCase;

class StripePaymentFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_and_confirm_payment_intent_in_stripe_test_mode(): void
    {
        if (!getenv('RUN_INTEGRATION_TESTS')) {
            $this->markTestSkipped('Set RUN_INTEGRATION_TESTS=1 to run external integration tests.');
        }

        $secret = getenv('STRIPE_TEST_SECRET_KEY') ?: '';
        if ($secret === '') {
            $this->markTestSkipped('Missing STRIPE_TEST_SECRET_KEY.');
        }

        Stripe::setApiKey($secret);

        $pi = PaymentIntent::create([
            'amount' => 1999,
            'currency' => 'usd',
            'payment_method' => 'pm_card_visa',
            'confirm' => true,
        ]);

        $this->assertContains($pi->status, ['succeeded', 'requires_action', 'requires_capture', 'processing']);
    }
}

