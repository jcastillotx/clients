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

    public string $staffAssignmentRole = 'account_manager';

    /**
     * Get all available staff assignment roles for client assignments.
     */
    public static function getStaffAssignmentRoles(): array
    {
        return [
            'account_manager' => 'Account Manager',
            'project_lead' => 'Project Lead',
            'marketing_director' => 'Marketing Director/VP',
            'business_development_manager' => 'Business Development Manager',
            'creative_director' => 'Creative Director',
            'graphic_designer' => 'Graphic Designer',
            'copywriter' => 'Copywriter',
            'videographer_photographer' => 'Videographer/Photographer',
            'digital_marketing_manager' => 'Digital Marketing Manager',
            'seo_specialist' => 'SEO Specialist',
            'ppc_specialist' => 'PPC Specialist',
            'social_media_manager' => 'Social Media Manager',
            'email_marketing_specialist' => 'Email Marketing Specialist',
            'web_developer' => 'Web Developer',
            'ux_ui_designer' => 'UX/UI Designer',
            'crm_manager' => 'CRM Manager',
            'marketing_analyst' => 'Marketing Analyst',
            'data_scientist' => 'Data Scientist/Analyst',
            'client_services_manager' => 'Client Services Manager',
            'customer_support_manager' => 'Customer Support/Community Manager',
            'project_manager' => 'Project Manager',
            'hr_manager' => 'HR Manager',
            'administrative_assistant' => 'Administrative Assistant',
            'bookkeeper' => 'Bookkeeper/Accountant',
            'legal_advisor' => 'Legal Advisor',
            'pr_manager' => 'PR Manager',
            'event_planner' => 'Event Planner',
            'influencer_marketing_manager' => 'Influencer Marketing Manager',
        ];
    }

    protected function rules(): array
    {
        $roles = Role::query()->where('guard_name', 'web')->pluck('name')->all();
        $staffAssignmentRoles = array_keys(self::getStaffAssignmentRoles());

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
            'staffAssignmentRole' => ['required', Rule::in($staffAssignmentRoles)],
        ];
    }

    public function updated(string $property): void
    {
        if ($property === 'role') {
            if ($this->role !== 'client') {
                $this->client_id = null;
                $this->createNewClient = false;
            }
            // Clear staff-specific data if not a staff-type role
            $staffTypeRoles = [
                'staff', 'project_manager', 'developer', 'designer', 'copywriter',
                'marketing_director', 'account_manager', 'business_development_manager',
                'creative_director', 'graphic_designer', 'videographer_photographer',
                'digital_marketing_manager', 'seo_specialist', 'ppc_specialist',
                'social_media_manager', 'email_marketing_specialist',
                'crm_manager', 'marketing_analyst', 'data_scientist',
                'client_services_manager', 'customer_support_manager',
                'hr_manager', 'administrative_assistant',
                'bookkeeper', 'legal_advisor',
                'pr_manager', 'event_planner', 'influencer_marketing_manager',
            ];
            if (!in_array($this->role, $staffTypeRoles, true)) {
                $this->staffPermissions = [];
                $this->assignedClientIds = [];
            }
        }
    }

    protected function permissionGroups(): array
    {
        $all = Permission::query()->where('guard_name', 'web')->orderBy('name')->pluck('name')->all();

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

        foreach ($all as $p) {
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

        // Staff and staff sub-roles: sync permissions and client assignments
        $staffTypeRoles = [
            'staff', 'project_manager', 'developer', 'designer', 'copywriter',
            'marketing_director', 'account_manager', 'business_development_manager',
            'creative_director', 'graphic_designer', 'videographer_photographer',
            'digital_marketing_manager', 'seo_specialist', 'ppc_specialist',
            'social_media_manager', 'email_marketing_specialist',
            'crm_manager', 'marketing_analyst', 'data_scientist',
            'client_services_manager', 'customer_support_manager',
            'hr_manager', 'administrative_assistant',
            'bookkeeper', 'legal_advisor',
            'pr_manager', 'event_planner', 'influencer_marketing_manager',
        ];
        if (in_array($data['role'], $staffTypeRoles, true)) {
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
        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name')->all();
        $clients = Client::query()->orderBy('company_name')->get(['id', 'company_name']);

        return view('livewire.admin.users.create', [
            'roles' => $roles,
            'clients' => $clients,
            'permissionGroups' => $this->permissionGroups(),
            'staffAssignmentRoles' => self::getStaffAssignmentRoles(),
        ])->layout('layouts.admin', ['title' => 'Add User']);
    }
}
