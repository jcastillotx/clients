<?php

namespace Tests\Security;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Request as ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_cannot_view_other_clients_request_in_web_ui(): void
    {
        $clientA = Client::factory()->create();
        $clientB = Client::factory()->create();
        $userB = User::factory()->forClient($clientB)->create();

        $reqA = ServiceRequest::factory()->create(['client_id' => $clientA->id]);

        $this->actingAs($userB)
            ->get(route('requests.show', $reqA))
            ->assertForbidden();
    }

    public function test_client_cannot_view_other_clients_invoice_in_web_ui(): void
    {
        $clientA = Client::factory()->create();
        $clientB = Client::factory()->create();
        $userB = User::factory()->forClient($clientB)->create();

        $invA = Invoice::factory()->create(['client_id' => $clientA->id]);

        $this->actingAs($userB)
            ->get(route('invoices.show', $invA))
            ->assertForbidden();
    }

    public function test_client_cannot_view_other_clients_request_via_api(): void
    {
        $clientA = Client::factory()->create();
        $clientB = Client::factory()->create();
        $userB = User::factory()->forClient($clientB)->create();

        $reqA = ServiceRequest::factory()->create(['client_id' => $clientA->id]);

        Sanctum::actingAs($userB, ['read']);

        $this->getJson("/api/v1/requests/{$reqA->id}")
            ->assertForbidden();
    }
}
