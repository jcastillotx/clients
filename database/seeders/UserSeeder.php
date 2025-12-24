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

        // Create staff user
        $staff = User::firstOrCreate(
            ['email' => 'staff@kre8ivdesigns.com'],
            [
                'name' => 'Staff Member',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $staff->assignRole('staff');

        // Create demo client user
        $demoClient = Client::where('email', 'demo@example.com')->first();
        if ($demoClient) {
            $clientUser = User::firstOrCreate(
                ['email' => 'client@demo.com'],
                [
                    'name' => 'Demo Client User',
                    'password' => Hash::make('password'),
                    'client_id' => $demoClient->id,
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );
            $clientUser->assignRole('client');
        }

        // Create users for other clients
        $clients = Client::where('email', '!=', 'demo@example.com')->get();
        foreach ($clients as $client) {
            $user = User::firstOrCreate(
                ['email' => 'user@' . parse_url($client->website ?? 'example.com', PHP_URL_HOST) ?: strtolower(str_replace(' ', '', $client->company_name)) . '.com'],
                [
                    'name' => $client->contact_name,
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
