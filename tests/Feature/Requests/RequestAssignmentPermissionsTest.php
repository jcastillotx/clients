<?php

namespace Tests\Feature\Requests;

use App\Models\Client;
use App\Models\Request as ServiceRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RequestAssignmentPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_super_admin_can_assign_requests(): void
    {
        $client = Client::factory()->create();
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $request = ServiceRequest::factory()->create([
            'client_id' => $client->id,
            'assigned_to' => null,
        ]);

        Livewire::actingAs($superAdmin)
            ->test(\App\Http\Livewire\Admin\Requests\AdminRequestDetail::class, ['request' => $request])
            ->set('assigned_to', $staff->id)
            ->call('saveAssignment')
            ->assertHasNoErrors();

        $request->refresh();
        $this->assertEquals($staff->id, $request->assigned_to);
    }

    public function test_admin_can_assign_requests(): void
    {
        $client = Client::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $request = ServiceRequest::factory()->create([
            'client_id' => $client->id,
            'assigned_to' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Http\Livewire\Admin\Requests\AdminRequestDetail::class, ['request' => $request])
            ->set('assigned_to', $staff->id)
            ->call('saveAssignment')
            ->assertHasNoErrors();

        $request->refresh();
        $this->assertEquals($staff->id, $request->assigned_to);
    }

    public function test_project_manager_can_assign_requests(): void
    {
        $client = Client::factory()->create();
        $projectManager = User::factory()->create();
        $projectManager->assignRole('project_manager');

        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $request = ServiceRequest::factory()->create([
            'client_id' => $client->id,
            'assigned_to' => null,
        ]);

        Livewire::actingAs($projectManager)
            ->test(\App\Http\Livewire\Admin\Requests\AdminRequestDetail::class, ['request' => $request])
            ->set('assigned_to', $staff->id)
            ->call('saveAssignment')
            ->assertHasNoErrors();

        $request->refresh();
        $this->assertEquals($staff->id, $request->assigned_to);
    }

    public function test_regular_staff_cannot_assign_requests(): void
    {
        $client = Client::factory()->create();
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $anotherStaff = User::factory()->create();
        $anotherStaff->assignRole('staff');

        $request = ServiceRequest::factory()->create([
            'client_id' => $client->id,
            'assigned_to' => null,
        ]);

        Livewire::actingAs($staff)
            ->test(\App\Http\Livewire\Admin\Requests\AdminRequestDetail::class, ['request' => $request])
            ->set('assigned_to', $anotherStaff->id)
            ->call('saveAssignment')
            ->assertSessionHas('error', 'You do not have permission to assign requests.');

        $request->refresh();
        $this->assertNull($request->assigned_to);
    }

    public function test_client_cannot_assign_requests(): void
    {
        $client = Client::factory()->create();
        $clientUser = User::factory()->forClient($client)->create();
        $clientUser->assignRole('client');

        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $request = ServiceRequest::factory()->create([
            'client_id' => $client->id,
            'assigned_to' => null,
        ]);

        Livewire::actingAs($clientUser)
            ->test(\App\Http\Livewire\Admin\Requests\AdminRequestDetail::class, ['request' => $request])
            ->set('assigned_to', $staff->id)
            ->call('saveAssignment')
            ->assertSessionHas('error', 'You do not have permission to assign requests.');

        $request->refresh();
        $this->assertNull($request->assigned_to);
    }

    public function test_super_admin_can_bulk_assign_requests(): void
    {
        $client = Client::factory()->create();
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $requests = ServiceRequest::factory()->count(3)->create([
            'client_id' => $client->id,
            'assigned_to' => null,
        ]);

        Livewire::actingAs($superAdmin)
            ->test(\App\Http\Livewire\Admin\Requests\AdminRequestManagement::class)
            ->set('selected', $requests->pluck('id')->toArray())
            ->set('bulkAssignedTo', $staff->id)
            ->call('applyBulkAssign')
            ->assertSessionHas('success', 'Bulk assignment updated.');

        foreach ($requests as $request) {
            $request->refresh();
            $this->assertEquals($staff->id, $request->assigned_to);
        }
    }

    public function test_admin_can_bulk_assign_requests(): void
    {
        $client = Client::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $requests = ServiceRequest::factory()->count(2)->create([
            'client_id' => $client->id,
            'assigned_to' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Http\Livewire\Admin\Requests\AdminRequestManagement::class)
            ->set('selected', $requests->pluck('id')->toArray())
            ->set('bulkAssignedTo', $staff->id)
            ->call('applyBulkAssign')
            ->assertSessionHas('success', 'Bulk assignment updated.');

        foreach ($requests as $request) {
            $request->refresh();
            $this->assertEquals($staff->id, $request->assigned_to);
        }
    }

    public function test_project_manager_can_bulk_assign_requests(): void
    {
        $client = Client::factory()->create();
        $projectManager = User::factory()->create();
        $projectManager->assignRole('project_manager');

        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $requests = ServiceRequest::factory()->count(2)->create([
            'client_id' => $client->id,
            'assigned_to' => null,
        ]);

        Livewire::actingAs($projectManager)
            ->test(\App\Http\Livewire\Admin\Requests\AdminRequestManagement::class)
            ->set('selected', $requests->pluck('id')->toArray())
            ->set('bulkAssignedTo', $staff->id)
            ->call('applyBulkAssign')
            ->assertSessionHas('success', 'Bulk assignment updated.');

        foreach ($requests as $request) {
            $request->refresh();
            $this->assertEquals($staff->id, $request->assigned_to);
        }
    }

    public function test_regular_staff_cannot_bulk_assign_requests(): void
    {
        $client = Client::factory()->create();
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $anotherStaff = User::factory()->create();
        $anotherStaff->assignRole('staff');

        $requests = ServiceRequest::factory()->count(2)->create([
            'client_id' => $client->id,
            'assigned_to' => null,
        ]);

        Livewire::actingAs($staff)
            ->test(\App\Http\Livewire\Admin\Requests\AdminRequestManagement::class)
            ->set('selected', $requests->pluck('id')->toArray())
            ->set('bulkAssignedTo', $anotherStaff->id)
            ->call('applyBulkAssign')
            ->assertSessionHas('error', 'You do not have permission to assign requests.');

        foreach ($requests as $request) {
            $request->refresh();
            $this->assertNull($request->assigned_to);
        }
    }

    public function test_super_admin_has_assign_request_permission(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->assertTrue($superAdmin->can('assign_request'));
    }

    public function test_admin_has_assign_request_permission(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($admin->can('assign_request'));
    }

    public function test_project_manager_has_assign_request_permission(): void
    {
        $projectManager = User::factory()->create();
        $projectManager->assignRole('project_manager');

        $this->assertTrue($projectManager->can('assign_request'));
    }

    public function test_regular_staff_does_not_have_assign_request_permission(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('staff');

        $this->assertFalse($staff->can('assign_request'));
    }

    public function test_client_does_not_have_assign_request_permission(): void
    {
        $client = Client::factory()->create();
        $clientUser = User::factory()->forClient($client)->create();
        $clientUser->assignRole('client');

        $this->assertFalse($clientUser->can('assign_request'));
    }
}
