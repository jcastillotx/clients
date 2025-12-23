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
        // Create demo client
        Client::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'company_name' => 'Demo Company LLC',
                'contact_name' => 'John Demo',
                'phone' => '(555) 123-4567',
                'address' => '123 Demo Street',
                'city' => 'Demo City',
                'state' => 'DC',
                'zip_code' => '12345',
                'country' => 'US',
                'website' => 'https://demo-company.com',
                'industry' => 'Technology',
                'status' => 'active',
                'tier' => 'premium',
                'notes' => 'Demo client account for testing purposes.',
            ]
        );

        // Create additional sample clients
        $clients = [
            [
                'company_name' => 'Acme Corporation',
                'contact_name' => 'Jane Smith',
                'email' => 'jane@acmecorp.com',
                'phone' => '(555) 234-5678',
                'city' => 'New York',
                'state' => 'NY',
                'industry' => 'Manufacturing',
                'status' => 'active',
                'tier' => 'enterprise',
            ],
            [
                'company_name' => 'TechStart Inc',
                'contact_name' => 'Mike Johnson',
                'email' => 'mike@techstart.io',
                'phone' => '(555) 345-6789',
                'city' => 'San Francisco',
                'state' => 'CA',
                'industry' => 'Software',
                'status' => 'active',
                'tier' => 'standard',
            ],
            [
                'company_name' => 'Local Business Shop',
                'contact_name' => 'Sarah Brown',
                'email' => 'sarah@localbiz.com',
                'phone' => '(555) 456-7890',
                'city' => 'Chicago',
                'state' => 'IL',
                'industry' => 'Retail',
                'status' => 'active',
                'tier' => 'basic',
            ],
        ];

        foreach ($clients as $clientData) {
            Client::firstOrCreate(
                ['email' => $clientData['email']],
                $clientData
            );
        }
    }
}
