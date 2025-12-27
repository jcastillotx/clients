<?php

namespace Tests\Feature\Auth;

use App\Models\Client;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_log_in_and_reach_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $client = Client::factory()->active()->create();
        $user = User::factory()->forClient($client)->create([
            'email' => 'client@example.com',
        ]);
        $user->assignRole('client');

        $this->post('/login', [
            'email' => 'client@example.com',
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);

        $this->get('/dashboard')->assertOk();
    }
}
