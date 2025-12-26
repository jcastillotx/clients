<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $qty = fake()->randomFloat(2, 1, 10);
        $unit = fake()->randomFloat(2, 50, 1500);

        return [
            // Prefer existing records to avoid ballooning seed data
            'invoice_id' => Invoice::query()->inRandomOrder()->value('id') ?? Invoice::factory(),
            'description' => fake()->sentence(6),
            'quantity' => $qty,
            'unit_price' => $unit,
            'total' => $qty * $unit,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}

