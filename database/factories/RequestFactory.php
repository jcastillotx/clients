<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Request;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Request>
 */
class RequestFactory extends Factory
{
    protected $model = Request::class;

    public function definition(): array
    {
        $statuses = ['pending', 'in_review', 'approved', 'in_progress', 'on_hold', 'completed', 'cancelled'];
        $status = fake()->randomElement($statuses);

        $dueDate = fake()->boolean(60) ? fake()->dateTimeBetween('now', '+30 days') : null;

        $startedAt = null;
        $completedAt = null;
        if (in_array($status, ['in_progress', 'on_hold', 'completed'], true)) {
            $startedAt = fake()->dateTimeBetween('-14 days', '-1 day');
        }
        if ($status === 'completed') {
            $completedAt = fake()->dateTimeBetween('-7 days', 'now');
        }

        return [
            // Prefer existing records to avoid ballooning seed data
            'client_id' => Client::query()->inRandomOrder()->value('id') ?? Client::factory(),
            'created_by' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'assigned_to' => null,
            'title' => fake()->sentence(6),
            'description' => fake()->paragraphs(3, true),
            'type' => fake()->randomElement(array_keys(config('client-portal.request_types'))),
            'status' => $status,
            'priority' => fake()->randomElement(array_keys(config('client-portal.request_priorities'))),
            'due_date' => $dueDate,
            'estimated_hours' => fake()->boolean(50) ? fake()->randomFloat(2, 1, 40) : null,
            'actual_hours' => null,
            'estimated_cost' => fake()->boolean(40) ? fake()->randomFloat(2, 100, 5000) : null,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ];
    }
}

