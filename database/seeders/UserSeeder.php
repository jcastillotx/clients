<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Skipping UserSeeder in production (prevents default credentials).');

            return;
        }

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@kre8ivdesigns.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $admin->assignRole('admin');

        // Create 3 test client users (one per test client company)
        $clients = Client::query()
            ->whereIn('email', ['client1@example.com', 'client2@example.com', 'client3@example.com'])
            ->get()
            ->keyBy('email');

        $users = [
            ['email' => 'client.user1@example.com', 'name' => 'Test Client User 1', 'client_email' => 'client1@example.com'],
            ['email' => 'client.user2@example.com', 'name' => 'Test Client User 2', 'client_email' => 'client2@example.com'],
            ['email' => 'client.user3@example.com', 'name' => 'Test Client User 3', 'client_email' => 'client3@example.com'],
        ];

        foreach ($users as $data) {
            $client = $clients->get($data['client_email'])
                ?? Client::factory()->active()->create(['email' => $data['client_email']]);

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'client_id' => $client->id,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $user->assignRole('client');
        }
    }
}
