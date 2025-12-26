<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            // Prefer existing records to avoid ballooning seed data
            'invoice_id' => Invoice::query()->inRandomOrder()->value('id') ?? Invoice::factory(),
            'client_id' => Client::query()->inRandomOrder()->value('id') ?? Client::factory(),
            'amount' => fake()->randomFloat(2, 50, 5000),
            'payment_method' => fake()->randomElement(['stripe']),
            'transaction_id' => fake()->optional()->uuid(),
            'stripe_payment_intent_id' => fake()->optional()->uuid(),
            'stripe_charge_id' => fake()->optional()->uuid(),
            'status' => fake()->randomElement(['pending', 'processing', 'succeeded', 'failed']),
            'failure_reason' => null,
            'metadata' => [],
            'processed_at' => now(),
        ];
    }
}

