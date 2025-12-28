<?php

namespace App\Http\Livewire\Admin\Users;

use App\Mail\UserInvitationMail;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Services\Entitlements\PortalEntitlementService;

class UserCreate extends Component
{
    public string $name = '';

    public string $email = '';

    public string $role = 'staff'; // admin|staff|client|custom

    public string $status = 'active'; // active|inactive|suspended

    public ?int $client_id = null;

    public bool $createNewClient = false;

    public string $client_company_name = '';

    public string $client_contact_name = '';

    public string $client_phone = '';

    /** @var array<int, string> */
    public array $staffPermissions = [];

    /** @var array<int, int> */
    public array $assignedClientIds = [];

    public string $staffAssignmentRole = 'account_manager'; // account_manager|project_lead

    protected function rules(): array
    {
        $roles = Role::query()->pluck('name')->all();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in($roles)],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],

            'createNewClient' => ['boolean'],
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')],
            'client_company_name' => [Rule::requiredIf(fn () => $this->role === 'client' && $this->createNewClient), 'string', 'max:255'],
            'client_contact_name' => [Rule::requiredIf(fn () => $this->role === 'client' && $this->createNewClient), 'string', 'max:255'],
            'client_phone' => ['nullable', 'string', 'max:255'],

            'staffPermissions' => ['array'],
            'staffPermissions.*' => ['string'],
            'assignedClientIds' => ['array'],
            'assignedClientIds.*' => ['integer', Rule::exists('clients', 'id')],
            'staffAssignmentRole' => ['required', Rule::in(['account_manager', 'project_lead'])],
        ];
    }

    public function updated(string $property): void
    {
        if ($property === 'role') {
            if ($this->role !== 'client') {
                $this->client_id = null;
                $this->createNewClient = false;
            }
            if ($this->role !== 'staff') {
                $this->staffPermissions = [];
                $this->assignedClientIds = [];
            }
        }
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

    public function save()
    {
        $data = $this->validate();

        $clientId = null;
        if ($data['role'] === 'client') {
            if ($this->createNewClient) {
                // Clients table requires unique email; we use the user's email as the client email.
                if (Client::query()->where('email', $data['email'])->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'email' => 'This email is already used by a client record.',
                    ]);
                }

                $client = Client::create([
                    'company_name' => $data['client_company_name'],
                    'contact_name' => $data['client_contact_name'],
                    'email' => $data['email'],
                    'phone' => $data['client_phone'] ?: null,
                    'status' => 'active',
                    'tier' => 'standard',
                ]);
                $clientId = $client->id;
            } else {
                if (! $data['client_id']) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'client_id' => 'Please select an existing client or create a new one.',
                    ]);
                }
                $clientId = (int) $data['client_id'];
            }
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Str::password(32), // hashed via cast
            'client_id' => $clientId,
            'is_active' => $data['status'] === 'active',
            'status' => $data['status'],
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$data['role']]);

        if ($data['role'] === 'staff') {
            $user->syncPermissions($this->staffPermissions);
            $user->syncAssignedClients($this->assignedClientIds, $this->staffAssignmentRole);
        }
        if ($data['role'] === 'client') {
            // Initialize manual permissions empty, then grant entitlements based on enabled features.
            $user->update(['manual_permissions' => []]);
            app(PortalEntitlementService::class)->syncUser($user);
        }

        // Create reset token + send invitation email (password setup link)
        $token = Password::broker()->createToken($user);
        $setPasswordUrl = url(route('password.reset', ['token' => $token, 'email' => $user->email], false));
        $roleLabel = str_replace('_', ' ', ucfirst($data['role']));
        Mail::to($user->email)->queue(new UserInvitationMail($user, $setPasswordUrl, $roleLabel));

        session()->flash('success', 'User created and invitation email queued.');

        return redirect()->route('admin.users.edit', $user);
    }

    public function render()
    {
        $roles = Role::query()->orderBy('name')->pluck('name')->all();
        $clients = Client::query()->orderBy('company_name')->get(['id', 'company_name']);

        return view('livewire.admin.users.create', [
            'roles' => $roles,
            'clients' => $clients,
            'permissionGroups' => $this->permissionGroups(),
        ])->layout('layouts.admin', ['title' => 'Add User']);
    }
}
