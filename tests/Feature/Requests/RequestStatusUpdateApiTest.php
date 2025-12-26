<?php

namespace Tests\Feature\Requests;

use App\Models\Client;
use App\Models\Request as ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RequestStatusUpdateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_update_own_request_status_and_timestamps_are_set(): void
    {
        $client = Client::factory()->create();
        $user = User::factory()->forClient($client)->create();

        $req = ServiceRequest::factory()->create([
            'client_id' => $client->id,
            'created_by' => $user->id,
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null,
        ]);

        Sanctum::actingAs($user, ['write']);

        $this->putJson("/api/v1/requests/{$req->id}/status", ['status' => 'in_progress'])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        $req->refresh();
        $this->assertNotNull($req->started_at);
        $this->assertNull($req->completed_at);

        $this->putJson("/api/v1/requests/{$req->id}/status", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $req->refresh();
        $this->assertNotNull($req->completed_at);
    }

    public function test_client_cannot_update_other_clients_request_status(): void
    {
        $clientA = Client::factory()->create();
        $clientB = Client::factory()->create();
        $userB = User::factory()->forClient($clientB)->create();

        $reqA = ServiceRequest::factory()->create([
            'client_id' => $clientA->id,
        ]);

        Sanctum::actingAs($userB, ['write']);

        $this->putJson("/api/v1/requests/{$reqA->id}/status", ['status' => 'in_review'])
            ->assertForbidden();
    }
}

