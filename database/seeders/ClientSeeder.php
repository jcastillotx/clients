<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Skipping ClientSeeder in production (demo data).');
            return;
        }

        // Create 3 test client companies (stable emails for dev/testing)
        $clients = [
            ['company_name' => 'Acme Corporation', 'contact_name' => 'Jane Smith', 'email' => 'client1@example.com', 'tier' => 'enterprise'],
            ['company_name' => 'TechStart Inc', 'contact_name' => 'Mike Johnson', 'email' => 'client2@example.com', 'tier' => 'standard'],
            ['company_name' => 'Local Business Shop', 'contact_name' => 'Sarah Brown', 'email' => 'client3@example.com', 'tier' => 'basic'],
        ];

        foreach ($clients as $data) {
            Client::firstOrCreate(
                ['email' => $data['email']],
                Client::factory()->active()->make($data)->toArray()
            );
        }
    }
}
