<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => fake()->company(),
            'contact_name' => fake()->name(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'zip_code' => fake()->postcode(),
            'country' => 'US',
            'website' => fake()->url(),
            'industry' => fake()->randomElement(['Technology', 'Healthcare', 'Finance', 'Retail', 'Manufacturing']),
            'status' => fake()->randomElement(['active', 'inactive', 'pending']),
            'tier' => fake()->randomElement(['basic', 'standard', 'premium', 'enterprise']),
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    /**
     * Indicate that the client is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the client is premium tier.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'tier' => 'premium',
        ]);
    }
}
