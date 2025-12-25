<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Contract;
use Illuminate\Database\Seeder;

class ContractSeeder extends Seeder
{
    public function run(): void
    {
        $clients = Client::query()->take(3)->get();
        if ($clients->isEmpty()) {
            $clients = Client::factory()->count(3)->active()->create();
        }

        // Create 3 contracts across clients
        foreach (range(1, 3) as $i) {
            Contract::factory()->create([
                'client_id' => $clients->random()->id,
            ]);
        }
    }
}

