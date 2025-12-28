<?php

namespace App\Http\Livewire\Admin\Users;

use App\Models\Client;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Services\Entitlements\PortalEntitlementService;

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

    /** @var array<int, string> Manual overrides (stored on users.manual_permissions) */
    public array $directPermissions = [];

    /** @var array<int, int> */
    public array $assignedClientIds = [];

    public string $staffAssignmentRole = 'account_manager';

    public function mount(User $user): void
    {
        $this->user = $user->load(['roles', 'client', 'permissions']);
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->status = $this->user->status ?? ($this->user->is_active ? 'active' : 'inactive');
        $this->two_factor_enabled = (bool) ($this->user->two_factor_enabled ?? false);

        $this->role = $this->user->roles->pluck('name')->first() ?? 'staff';
        // Back-compat: keep using $directPermissions as the UI model, but persist it to
        // users.manual_permissions and merge with entitlements for client users.
        $this->directPermissions = (array) ($this->user->manual_permissions ?? []);

        $this->client_id = $this->user->client_id;
        $this->assignedClientIds = $this->user->assignedClients()->pluck('clients.id')->map(fn ($id) => (int) $id)->all();
        $this->staffAssignmentRole = (string) ($this->user->assignedClients()->first()?->pivot?->role
            ?? $this->user->assignedClients()->first()?->pivot?->relationship
            ?? 'account_manager');
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
            'staffAssignmentRole' => ['required', Rule::in(['account_manager', 'project_lead'])],
            'confirmRoleDowngrade' => ['boolean'],
        ];
    }

    protected function permissionGroups(): array
    {
        $all = Permission::query()->orderBy('name')->pluck('name')->all();
        $clientAssignable = (array) config('entitlements.client_assignable_permissions', []);
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
            if ($this->role === 'client' && ! in_array($p, $clientAssignable, true)) {
                continue;
            }
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
        if ($this->isDowngrade($currentRole, $data['role']) && ! $data['confirmRoleDowngrade']) {
            session()->flash('error', 'Please confirm role downgrade before saving.');

            return;
        }

        // If role is client, ensure client_id is set
        if ($data['role'] === 'client' && ! $data['client_id']) {
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

        // Persist manual permissions (used for both staff and client portal users).
        $this->user->update([
            'manual_permissions' => array_values(array_unique($this->directPermissions)),
        ]);

        // Staff: keep client assignments.
        if ($data['role'] === 'staff') {
            $this->user->syncAssignedClients($this->assignedClientIds, $this->staffAssignmentRole);
        } else {
            $this->user->syncAssignedClients([], $this->staffAssignmentRole);
        }

        // Sync effective permissions:
        // - client: manual + entitlements (from enabled features)
        // - others: just manual (admins typically get permissions via roles anyway)
        if ($data['role'] === 'client') {
            app(PortalEntitlementService::class)->syncUser($this->user);
        } else {
            $this->user->syncPermissions((array) ($this->user->manual_permissions ?? []));
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
