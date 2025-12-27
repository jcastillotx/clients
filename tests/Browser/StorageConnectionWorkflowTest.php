<?php

namespace Tests\Browser;

use App\Models\Client;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class StorageConnectionWorkflowTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_storage_connection_workflow_example(): void
    {
        if (!env('RUN_DUSK_TESTS')) {
            $this->markTestSkipped('Set RUN_DUSK_TESTS=1 to run browser tests.');
        }

        $this->seed(RolePermissionSeeder::class);

        $client = Client::factory()->create();
        $user = User::factory()->forClient($client)->create([
            'email' => 'client-storage@example.com',
        ]);
        $user->assignRole('client');

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'client-storage@example.com')
                ->type('password', 'password')
                ->press('Log in')
                ->assertPathIs('/dashboard');

            $browser->visit('/storage/settings')
                ->assertSee('Storage');

            // TODO: Click "Connect Dropbox"/"Connect Google Drive"/"Connect S3" and complete OAuth / credential flow.
            // This usually requires a real test account + env configuration.
        });
    }
}

