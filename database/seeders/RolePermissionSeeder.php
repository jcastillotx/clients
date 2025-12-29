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
            'assign_request', // Ability to assign requests to staff

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

            // admin - delegated admin (restricted from critical system operations)
            $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard]);
            $adminPermissions = $allPermissions->reject(function ($permission) {
                return in_array($permission->name, [
                    'delete_user',        // Can manage users but not delete them
                    'manage_permissions', // Cannot modify role permissions
                    'view_settings',      // Cannot view system settings
                    'update_settings',    // Cannot modify system settings
                ]);
            });
            $adminRole->syncPermissions($adminPermissions);

            // project_manager - can assign requests to staff, higher than staff but below admin
            $projectManagerRole = Role::firstOrCreate(['name' => 'project_manager', 'guard_name' => $guard]);
            $projectManagerRole->syncPermissions([
                'access admin panel',

                // Clients
                'view_any_client',
                'view_client',
                'create_client',
                'update_client',

                // Requests (including assign)
                'view_any_request',
                'view_request',
                'create_request',
                'update_request',
                'delete_request',
                'assign_request',

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

            // staff - operational access (generic staff without assignment capability)
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

            // Staff sub-roles: developer, designer, copywriter (assignable staff)
            // These have similar permissions to staff but are specialized roles
            $staffSubRoles = ['developer', 'designer', 'copywriter'];
            foreach ($staffSubRoles as $subRole) {
                $role = Role::firstOrCreate(['name' => $subRole, 'guard_name' => $guard]);
                $role->syncPermissions([
                    'access admin panel',

                    // Clients (view only)
                    'view_any_client',
                    'view_client',

                    // Requests (work on assigned requests)
                    'view_any_request',
                    'view_request',
                    'update_request',

                    // Invoices (view only)
                    'view_any_invoice',
                    'view_invoice',

                    // Contracts (view only)
                    'view_any_contract',
                    'view_contract',

                    // Documents
                    'view_any_document',
                    'view_document',
                    'upload_document',

                    // Users (view only)
                    'view_any_user',
                    'view_user',

                    // Reporting
                    'view reports',
                ]);
            }

            // Client role - limited portal access
            $clientRole = Role::firstOrCreate(['name' => 'client', 'guard_name' => $guard]);
            $clientRole->syncPermissions([
                'view_client',
                'view_request',
                'create_request',
                'update_request',
                'delete_request',  // Clients can delete their own requests (with reason tracking)
                'view_contract',   // View, download, sign, and provide feedback on contracts
                'view_invoice',    // View invoices and ask questions
                'process_payment', // Process payments on invoices
                'view_document',
                'upload_document',
            ]);
        }
    }
}
