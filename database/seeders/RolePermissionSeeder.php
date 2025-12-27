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
            // Admin shell access
            'access admin panel',

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

            // Users & Permissions
            'view_any_user',
            'view_user',
            'create_user',
            'update_user',
            'delete_user',
            'manage_permissions',

            // Settings
            // (Keep both legacy + newer names; code uses "manage settings")
            'view_settings',
            'update_settings',
            'manage settings',

            // Documents (admin helpers; some UI gates on this)
            'manage documents',

            // Reporting (route middleware uses this)
            'view reports',
        ];

        // Support both the session ("web") and token ("sanctum") guards so
        // permission checks work consistently for API tokens.
        $guards = ['web', 'sanctum'];

        foreach ($guards as $guard) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => $guard,
                ]);
            }
        }

        // Create roles and assign permissions

        foreach ($guards as $guard) {
            $allPermissions = Permission::query()->where('guard_name', $guard)->get();

            // super_admin - full access
            $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $guard]);
            $superAdminRole->syncPermissions($allPermissions);

            // admin - full access
            $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard]);
            $adminRole->syncPermissions($allPermissions);

            // staff - operational access
            $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => $guard]);
            $staffRole->syncPermissions([
                'access admin panel',

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

                // Users (limited)
                'view_any_user',
                'view_user',

                // Settings (limited)
                'manage settings',

                // Reporting
                'view reports',
            ]);

            // Client role - limited access
            $clientRole = Role::firstOrCreate(['name' => 'client', 'guard_name' => $guard]);
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
}
