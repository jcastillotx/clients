<?php

namespace App\Http\Livewire\Admin\Users;

use App\Models\Client;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserEdit extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public User $user;

    public string $name = '';
    public string $email = '';
    public string $status = 'active';
    public bool $two_factor_enabled = false;

    public string $role = 'staff';
    public bool $confirmRoleDowngrade = false;

    public ?int $client_id = null;

    /** @var array<int, string> */
    public array $directPermissions = [];

    /** @var array<int, int> */
    public array $assignedClientIds = [];

    public function mount(User $user): void
    {
        $this->user = $user->load(['roles', 'client', 'permissions']);
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->status = $this->user->status ?? ($this->user->is_active ? 'active' : 'inactive');
        $this->two_factor_enabled = (bool) ($this->user->two_factor_enabled ?? false);

        $this->role = $this->user->roles->pluck('name')->first() ?? 'staff';
        $this->directPermissions = $this->user->permissions->pluck('name')->values()->all();

        $this->client_id = $this->user->client_id;
        $this->assignedClientIds = $this->user->assignedClients()->pluck('clients.id')->map(fn ($id) => (int) $id)->all();
    }

    protected function rules(): array
    {
        $roles = Role::query()->pluck('name')->all();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user->id)],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'two_factor_enabled' => ['boolean'],
            'role' => ['required', Rule::in($roles)],
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')],
            'directPermissions' => ['array'],
            'directPermissions.*' => ['string'],
            'assignedClientIds' => ['array'],
            'assignedClientIds.*' => ['integer', Rule::exists('clients', 'id')],
            'confirmRoleDowngrade' => ['boolean'],
        ];
    }

    protected function permissionGroups(): array
    {
        $all = Permission::query()->orderBy('name')->pluck('name')->all();
        $groups = [
            'Clients' => [],
            'Requests' => [],
            'Invoices' => [],
            'Contracts' => [],
            'Documents' => [],
            'Users' => [],
            'Settings' => [],
            'Other' => [],
        ];

        foreach ($all as $p) {
            $group = match (true) {
                str_contains($p, '_client') => 'Clients',
                str_contains($p, '_request') => 'Requests',
                str_contains($p, '_invoice') || str_contains($p, 'process_payment') => 'Invoices',
                str_contains($p, '_contract') => 'Contracts',
                str_contains($p, '_document') => 'Documents',
                str_contains($p, '_user') || str_contains($p, 'manage_permissions') => 'Users',
                str_contains($p, '_settings') => 'Settings',
                default => 'Other',
            };
            $groups[$group][] = $p;
        }

        return $groups;
    }

    protected function isDowngrade(string $fromRole, string $toRole): bool
    {
        $rank = fn (string $r) => match ($r) {
            'super_admin' => 4,
            'admin' => 3,
            'staff' => 2,
            'client' => 1,
            default => 2,
        };

        return $rank($toRole) < $rank($fromRole);
    }

    public function save(): void
    {
        $data = $this->validate();

        $currentRole = $this->user->roles->pluck('name')->first() ?? 'staff';
        if ($this->isDowngrade($currentRole, $data['role']) && !$data['confirmRoleDowngrade']) {
            session()->flash('error', 'Please confirm role downgrade before saving.');
            return;
        }

        // If role is client, ensure client_id is set
        if ($data['role'] === 'client' && !$data['client_id']) {
            session()->flash('error', 'Client users must be linked to a client.');
            return;
        }

        $this->user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'status' => $data['status'],
            'is_active' => $data['status'] === 'active',
            'two_factor_enabled' => (bool) $data['two_factor_enabled'],
            'client_id' => $data['role'] === 'client' ? $data['client_id'] : null,
        ]);

        $this->user->syncRoles([$data['role']]);

        // Staff: keep direct permission selections + client assignments
        if ($data['role'] === 'staff') {
            $this->user->syncPermissions($this->directPermissions);
            $this->user->assignedClients()->sync($this->assignedClientIds);
        } else {
            // Non-staff: clear staff-only constructs
            $this->user->syncPermissions([]);
            $this->user->assignedClients()->sync([]);
        }

        $this->confirmRoleDowngrade = false;
        $this->user->refresh()->load(['roles', 'client', 'permissions']);

        session()->flash('success', 'User updated.');
    }

    public function sendPasswordReset(): void
    {
        Password::sendResetLink(['email' => $this->user->email]);
        session()->flash('success', 'Password reset link sent.');
    }

    public function render()
    {
        $roles = Role::query()->orderBy('name')->pluck('name')->all();
        $clients = Client::query()->orderBy('company_name')->get(['id', 'company_name']);
        $loginHistory = LoginHistory::query()
            ->where('user_id', $this->user->id)
            ->orderByDesc('logged_in_at')
            ->paginate(15);

        return view('livewire.admin.users.edit', [
            'roles' => $roles,
            'clients' => $clients,
            'permissionGroups' => $this->permissionGroups(),
            'loginHistory' => $loginHistory,
            'currentRole' => $this->user->roles->pluck('name')->first() ?? 'staff',
        ])->layout('layouts.admin', ['title' => 'Edit User']);
    }
}

