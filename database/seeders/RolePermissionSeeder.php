<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions (requested naming)
        $permissions = [
            // Clients
            'view_any_client',
            'view_client',
            'create_client',
            'update_client',
            'delete_client',

            // Requests
            'view_any_request',
            'view_request',
            'create_request',
            'update_request',
            'delete_request',

            // Invoices
            'view_any_invoice',
            'view_invoice',
            'create_invoice',
            'update_invoice',
            'delete_invoice',
            'process_payment',

            // Contracts
            'view_any_contract',
            'view_contract',
            'create_contract',
            'update_contract',

            // Documents
            'view_any_document',
            'view_document',
            'upload_document',
            'delete_document',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        $allPermissions = Permission::all();

        // super_admin - full access
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdminRole->syncPermissions($allPermissions);

        // admin - full access
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions($allPermissions);

        // staff - operational access
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staffRole->syncPermissions([
            // Clients
            'view_any_client',
            'view_client',
            'create_client',
            'update_client',

            // Requests
            'view_any_request',
            'view_request',
            'create_request',
            'update_request',
            'delete_request',

            // Invoices
            'view_any_invoice',
            'view_invoice',
            'create_invoice',
            'update_invoice',
            'process_payment',

            // Contracts
            'view_any_contract',
            'view_contract',
            'create_contract',
            'update_contract',

            // Documents
            'view_any_document',
            'view_document',
            'upload_document',
            'delete_document',
        ]);

        // Client role - limited access
        $clientRole = Role::firstOrCreate(['name' => 'client']);
        $clientRole->syncPermissions([
            'view_client',
            'view_request',
            'create_request',
            'update_request',
            'view_contract',
            'view_invoice',
            'process_payment',
            'view_document',
            'upload_document',
        ]);
    }
}
