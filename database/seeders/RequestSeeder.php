<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Request;
use App\Models\User;
use Illuminate\Database\Seeder;

class RequestSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Skipping RequestSeeder in production (demo data).');

            return;
        }

        $clients = Client::query()->take(3)->get();
        if ($clients->isEmpty()) {
            $clients = Client::factory()->count(3)->active()->create();
        }

        // Create 10 sample requests total across clients
        Request::factory()
            ->count(10)
            ->make()
            ->each(function (Request $request) use ($clients) {
                $client = $clients->random();
                $creator = User::query()->where('client_id', $client->id)->first()
                    ?? User::factory()->forClient($client)->create();

                $request->client_id = $client->id;
                $request->created_by = $creator->id;
                $request->save();
            });
    }
}
