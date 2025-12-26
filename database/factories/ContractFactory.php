<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['draft', 'pending_signature', 'active', 'expired', 'terminated']);

        $start = fake()->dateTimeBetween('-6 months', 'now');
        $end = fake()->boolean(75) ? fake()->dateTimeBetween('now', '+12 months') : null;

        // Make expired contracts actually expired
        if ($status === 'expired') {
            $start = fake()->dateTimeBetween('-18 months', '-6 months');
            $end = fake()->dateTimeBetween('-5 months', '-1 day');
        }

        $signedAt = null;
        $signedBy = null;
        if (in_array($status, ['active', 'expired'], true) && fake()->boolean(70)) {
            $signedAt = fake()->dateTimeBetween('-12 months', 'now');
            $signedBy = fake()->name();
        }

        return [
            // Prefer existing records to avoid ballooning seed data
            'client_id' => Client::query()->inRandomOrder()->value('id') ?? Client::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'file_path' => null,
            'contract_number' => null,
            'start_date' => $start,
            'end_date' => $end,
            'value' => fake()->randomFloat(2, 500, 50000),
            'status' => $status,
            'signed_at' => $signedAt,
            'signed_by' => $signedBy,
            'signature_ip' => null,
            'signature_data' => null,
        ];
    }
}

