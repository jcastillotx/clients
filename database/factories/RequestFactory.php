<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Request;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Request>
 */
class RequestFactory extends Factory
{
    protected $model = Request::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'created_by' => User::factory(),
            'assigned_to' => null,
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(),
            'type' => 'support',
            'status' => 'pending',
            'priority' => 'medium',
            'due_date' => null,
            'estimated_hours' => null,
            'actual_hours' => null,
            'estimated_cost' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }
}

