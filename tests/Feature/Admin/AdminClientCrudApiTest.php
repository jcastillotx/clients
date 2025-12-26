<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminClientCrudApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_token_can_create_and_view_client_via_api(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['client_id' => null]);
        // API tokens authenticate via the "sanctum" guard; assign a matching-guard role.
        $admin->assignRole(Role::findByName('admin', 'sanctum'));
        $this->assertTrue($admin->hasRole('admin', 'sanctum'));

        Sanctum::actingAs($admin, ['admin']);

        $create = $this->postJson('/api/v1/clients', [
            'company_name' => 'Acme LLC',
            'contact_name' => 'Jane Doe',
            'email' => 'acme@example.com',
        ])->assertCreated();

        $clientId = (int) ($create->json('data.id') ?? 0);
        $this->assertGreaterThan(0, $clientId);

        $this->getJson("/api/v1/clients/{$clientId}")
            ->assertOk()
            ->assertJsonPath('data.email', 'acme@example.com');
    }
}

