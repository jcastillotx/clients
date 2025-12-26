<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $issue = fake()->dateTimeBetween('-90 days', '-3 days');
        $due = (clone $issue);
        $due = (new \DateTimeImmutable($due->format('c')))->modify('+' . fake()->numberBetween(7, 30) . ' days');

        $status = fake()->randomElement(['draft', 'sent', 'paid', 'overdue']);

        // Make overdue consistent
        if ($status === 'overdue') {
            $issue = fake()->dateTimeBetween('-90 days', '-45 days');
            $due = (new \DateTimeImmutable($issue->format('c')))->modify('+14 days');
            $due = $due->modify('-5 days');
        }

        $paidAt = null;
        if ($status === 'paid') {
            $paidAt = fake()->dateTimeBetween('-30 days', 'now');
        }

        return [
            // Prefer existing records to avoid ballooning seed data
            'client_id' => Client::query()->inRandomOrder()->value('id') ?? Client::factory(),
            'request_id' => null,
            'invoice_number' => null,
            'subtotal' => 0,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'discount' => 0,
            'amount' => 0,
            'issue_date' => $issue,
            'due_date' => $due,
            'paid_at' => $paidAt,
            'status' => $status,
            'notes' => fake()->optional()->sentence(),
            'terms' => fake()->optional()->sentence(),
            'pdf_path' => null,
        ];
    }
}

