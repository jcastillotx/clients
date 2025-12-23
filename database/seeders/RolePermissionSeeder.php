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

        // Create permissions
        $permissions = [
            // Request permissions
            'view requests',
            'create requests',
            'edit requests',
            'delete requests',
            'manage all requests',
            
            // Contract permissions
            'view contracts',
            'sign contracts',
            'manage contracts',
            
            // Invoice permissions
            'view invoices',
            'pay invoices',
            'manage invoices',
            
            // Document permissions
            'view documents',
            'upload documents',
            'delete documents',
            'manage documents',
            
            // Client permissions
            'view clients',
            'create clients',
            'edit clients',
            'delete clients',
            
            // User permissions
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // Admin permissions
            'access admin panel',
            'view reports',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Admin role - full access
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Staff role - manage clients and work
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staffRole->givePermissionTo([
            'view requests',
            'create requests',
            'edit requests',
            'manage all requests',
            'view contracts',
            'manage contracts',
            'view invoices',
            'manage invoices',
            'view documents',
            'upload documents',
            'manage documents',
            'view clients',
            'view users',
            'access admin panel',
        ]);

        // Client role - limited access
        $clientRole = Role::firstOrCreate(['name' => 'client']);
        $clientRole->givePermissionTo([
            'view requests',
            'create requests',
            'edit requests',
            'view contracts',
            'sign contracts',
            'view invoices',
            'pay invoices',
            'view documents',
            'upload documents',
        ]);
    }
}
