<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiV1AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_read_token_can_get_client_but_cannot_create_request(): void
    {
        $client = Client::factory()->create();
        $user = User::factory()->create(['client_id' => $client->id]);

        Sanctum::actingAs($user, ['read']);

        $this->getJson("/api/v1/clients/{$client->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $client->id);

        $this->postJson('/api/v1/requests', [
            'title' => 'Test',
            'description' => 'Hello',
        ])->assertForbidden();
    }

    public function test_write_token_can_create_request(): void
    {
        $client = Client::factory()->create();
        $user = User::factory()->create(['client_id' => $client->id]);

        Sanctum::actingAs($user, ['write']);

        $this->postJson('/api/v1/requests', [
            'title' => 'Test',
            'description' => 'Hello',
        ])->assertCreated()
            ->assertJsonPath('data.client_id', $client->id)
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_admin_ability_required_to_create_client(): void
    {
        $staff = User::factory()->create(['client_id' => null]);

        Sanctum::actingAs($staff, ['write']);

        $this->postJson('/api/v1/clients', [
            'company_name' => 'Acme',
            'contact_name' => 'Jane',
            'email' => 'acme@example.com',
        ])->assertForbidden();
    }
}
