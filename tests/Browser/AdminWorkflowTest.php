<?php

namespace Tests\Browser;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminWorkflowTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_admin_workflow_create_client_assign_request_generate_invoice(): void
    {
        if (!env('RUN_DUSK_TESTS')) {
            $this->markTestSkipped('Set RUN_DUSK_TESTS=1 to run browser tests.');
        }

        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'client_id' => null,
        ]);
        $admin->assignRole('admin');

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'admin@example.com')
                ->type('password', 'password')
                ->press('Log in')
                ->assertPathIs('/dashboard');

            // Example: admin can reach reports dashboard (requires permission:view reports).
            $browser->visit('/admin/reports')
                ->assertSee('Reports');

            // TODO: Navigate to clients CRUD screens, create a client, assign a request, generate invoice.
        });
    }
}

