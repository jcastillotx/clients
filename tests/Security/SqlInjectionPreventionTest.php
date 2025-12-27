<?php

namespace Tests\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SqlInjectionPreventionTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_model_binding_blocks_non_numeric_ids_like_sql_injection_payloads(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['read']);

        // If this were interpolated into a SQL query, it could leak data; route binding prevents it.
        $this->getJson('/api/v1/clients/1%20OR%201%3D1')
            ->assertNotFound();
    }
}
