<?php

namespace App\Http\Livewire\Admin\Users;

use Illuminate\Support\Str;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Permissions extends Component
{
    public string $newRoleName = '';

    /** @var array<string, array<int, string>> */
    public array $permissionGroups = [];

    /** @var array<string, array<int, string>> roleName => [permissionName...] */
    public array $rolePermissions = [];

    public function mount(): void
    {
        $this->refreshMatrix();
    }

    public function refreshMatrix(): void
    {
        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->get();
        $perms = Permission::query()->where('guard_name', 'web')->orderBy('name')->pluck('name')->all();

        $groups = [
            'Clients' => [],
            'Requests' => [],
            'Leads' => [],
            'Invoices' => [],
            'Contracts' => [],
            'Documents' => [],
            'Users' => [],
            'Settings' => [],
            'Other' => [],
        ];

        foreach ($perms as $p) {
            $group = match (true) {
                str_contains($p, '_client') => 'Clients',
                str_contains($p, '_request') => 'Requests',
                str_contains($p, '_lead') => 'Leads',
                str_contains($p, '_invoice') || str_contains($p, 'process_payment') => 'Invoices',
                str_contains($p, '_contract') => 'Contracts',
                str_contains($p, '_document') => 'Documents',
                str_contains($p, '_user') || str_contains($p, 'manage_permissions') => 'Users',
                str_contains($p, '_settings') => 'Settings',
                default => 'Other',
            };
            $groups[$group][] = $p;
        }

        $this->permissionGroups = $groups;

        $map = [];
        foreach ($roles as $role) {
            $map[$role->name] = $role->permissions()->pluck('name')->values()->all();
        }
        $this->rolePermissions = $map;
    }

    public function createRole(): void
    {
        $name = Str::of($this->newRoleName)->trim()->lower()->replace(' ', '_')->value();
        if ($name === '') {
            session()->flash('error', 'Role name is required.');

            return;
        }

        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        $this->newRoleName = '';
        $this->refreshMatrix();
        session()->flash('success', 'Role created.');
    }

    public function toggle(string $roleName, string $permissionName): void
    {
        $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
        $perm = Permission::query()->where('name', $permissionName)->where('guard_name', 'web')->firstOrFail();

        if ($role->hasPermissionTo($perm)) {
            $role->revokePermissionTo($perm);
        } else {
            $role->givePermissionTo($perm);
        }

        $this->refreshMatrix();
    }

    public function render()
    {
        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name')->all();

        return view('livewire.admin.users.permissions', [
            'roles' => $roles,
        ])->layout('layouts.admin', ['title' => 'Permissions']);
    }
}
