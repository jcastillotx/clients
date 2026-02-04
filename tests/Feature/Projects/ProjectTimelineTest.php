<?php

namespace Tests\Feature\Projects;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_project_timeline(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(\App\Http\Livewire\Projects\ProjectTimeline::class)
            ->assertStatus(200)
            ->assertSee('Project timeline')
            ->assertSet('requestId', null);
    }

    public function test_request_id_handles_empty_string(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Test that setting requestId to empty string is handled correctly
        Livewire::actingAs($admin)
            ->test(\App\Http\Livewire\Projects\ProjectTimeline::class)
            ->set('requestId', '')
            ->assertSet('requestId', null);
    }

    public function test_request_id_handles_numeric_string(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Test that setting requestId to a numeric string is converted to int
        Livewire::actingAs($admin)
            ->test(\App\Http\Livewire\Projects\ProjectTimeline::class)
            ->set('requestId', '123')
            ->assertSet('requestId', 123);
    }

    public function test_request_id_handles_invalid_string(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Test that setting requestId to an invalid string is treated as null
        Livewire::actingAs($admin)
            ->test(\App\Http\Livewire\Projects\ProjectTimeline::class)
            ->set('requestId', 'invalid')
            ->assertSet('requestId', null);
    }
}
