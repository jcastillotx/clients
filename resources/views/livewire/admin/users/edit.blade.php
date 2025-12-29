<div class="max-w-7xl mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <p class="text-sm text-slate-500">Admin</p>
            <h1 class="text-2xl font-semibold text-slate-900">Edit User</h1>
            <p class="text-sm text-slate-500 mt-1">{{ $user->email }}</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
            Back
        </a>
    </div>

    {{-- Flash Messages & Validation Errors --}}
    @include('partials.flash-messages')

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
        <!-- Main Column -->
        <div class="xl:col-span-3 space-y-6">
            <!-- Account Card -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-base font-semibold text-slate-900">Account</h2>
                </div>
                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Name</label>
                            <input type="text" wire:model.live.debounce.350ms="name" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            @error('name')
                                <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Email</label>
                            <input type="email" wire:model.live.debounce.350ms="email" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            @error('email')
                                <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Status</label>
                            <select wire:model.live="status" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                            @error('status')
                                <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Role</label>
                            <select wire:model.live="role" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                @foreach($roles as $r)
                                    <option value="{{ $r }}">{{ str_replace('_', ' ', ucfirst($r)) }}</option>
                                @endforeach
                            </select>
                            @error('role')
                                <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">2FA</label>
                            <div class="pt-2">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" wire:model.live="two_factor_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                    <span class="text-sm text-slate-700">Enabled</span>
                                </label>
                            </div>
                            <p class="mt-1.5 text-xs text-slate-500">Flag only (no enforcement yet).</p>
                        </div>
                    </div>

                    @php
                        $downgrade = false;
                        $rank = fn ($r) => match ($r) {
                            'super_admin' => 10,
                            'admin' => 9,
                            'marketing_director' => 8,
                            'project_manager', 'creative_director', 'digital_marketing_manager', 'client_services_manager' => 7,
                            'staff', 'account_manager', 'business_development_manager', 'crm_manager', 'customer_support_manager', 'hr_manager' => 6,
                            'developer', 'designer', 'copywriter', 'graphic_designer', 'videographer_photographer',
                            'seo_specialist', 'ppc_specialist', 'social_media_manager', 'email_marketing_specialist',
                            'marketing_analyst', 'data_scientist', 'bookkeeper', 'legal_advisor',
                            'pr_manager', 'event_planner', 'influencer_marketing_manager' => 5,
                            'administrative_assistant' => 4,
                            'client' => 1,
                            default => 5,
                        };
                        $downgrade = $rank($role) < $rank($currentRole);
                    @endphp

                    @if($downgrade)
                        <div class="rounded-xl bg-amber-50 border border-amber-200 p-4">
                            <p class="text-sm text-amber-800 mb-2">
                                You are downgrading permissions from <strong>{{ $currentRole }}</strong> to <strong>{{ $role }}</strong>.
                            </p>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model.live="confirmRoleDowngrade" class="h-4 w-4 rounded border-amber-400 text-amber-600 focus:ring-amber-500 focus:ring-offset-0">
                                <span class="text-sm text-amber-800">Confirm role downgrade</span>
                            </label>
                        </div>
                    @endif

                    @if($role === 'client')
                        <div class="border-t border-slate-200 pt-5">
                            <h3 class="text-sm font-semibold text-slate-900 mb-4">Client Link</h3>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Client</label>
                                <select wire:model.live="client_id" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                    <option value="">Select a client…</option>
                                    @foreach($clients as $c)
                                        <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                                    @endforeach
                                </select>
                                @error('client_id')
                                    <div class="mt-1.5 text-xs text-rose-600">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endif

                    @php
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
                        $isStaffRole = in_array($role, $staffTypeRoles);
                    @endphp

                    @if($isStaffRole || $role === 'client')
                        <div class="border-t border-slate-200 pt-5">
                            <h3 class="text-sm font-semibold text-slate-900 mb-4">{{ $isStaffRole ? 'Staff Assignments' : 'Portal Permissions' }}</h3>
                        </div>
                    @endif

                    @if($isStaffRole)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Assignment Role</label>
                                <select wire:model.live="staffAssignmentRole" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors mb-2">
                                    @foreach($staffAssignmentRoles as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Assigned Clients</label>
                                <select multiple size="6" wire:model.live="assignedClientIds" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                                    @foreach($clients as $c)
                                        <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Manual Permissions</label>
                                <div class="rounded-xl border border-slate-200 p-3 max-h-72 overflow-auto space-y-3">
                                    @foreach($permissionGroups as $group => $perms)
                                        @if(empty($perms)) @continue @endif
                                        <div>
                                            <p class="text-xs font-semibold text-slate-700 mb-1">{{ $group }}</p>
                                            <div class="space-y-1">
                                                @foreach($perms as $p)
                                                    <label class="flex items-center gap-2 cursor-pointer">
                                                        <input type="checkbox" value="{{ $p }}" wire:model.live="directPermissions" class="h-3.5 w-3.5 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                                        <span class="text-xs text-slate-600">{{ $p }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="mt-2 text-xs text-slate-500">Direct overrides in addition to role permissions.</p>
                            </div>
                        </div>
                    @endif

                    @if($role === 'client')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="rounded-xl bg-blue-50 border border-blue-200 p-4">
                                <h4 class="text-sm font-semibold text-blue-900 mb-1">Automatic Permissions</h4>
                                <p class="text-xs text-blue-700">Client portal permissions are granted automatically based on enabled features and paid invoice items.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Manual Permissions</label>
                                <div class="rounded-xl border border-slate-200 p-3 max-h-72 overflow-auto space-y-3">
                                    @foreach($permissionGroups as $group => $perms)
                                        @if(empty($perms)) @continue @endif
                                        <div>
                                            <p class="text-xs font-semibold text-slate-700 mb-1">{{ $group }}</p>
                                            <div class="space-y-1">
                                                @foreach($perms as $p)
                                                    <label class="flex items-center gap-2 cursor-pointer">
                                                        <input type="checkbox" value="{{ $p }}" wire:model.live="directPermissions" class="h-3.5 w-3.5 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                                                        <span class="text-xs text-slate-600">{{ $p }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="mt-2 text-xs text-slate-500">Effective permissions = manual + entitlements.</p>
                            </div>
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-3 pt-4">
                        <button type="button" wire:click="save" wire:loading.attr="disabled" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
                            Save Changes
                        </button>
                        <button type="button" wire:click="openPasswordModal" class="rounded-lg border border-amber-500 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 hover:bg-amber-100 transition-colors">
                            <i class="fas fa-key mr-1"></i> Set Password
                        </button>
                        <button type="button" wire:click="sendPasswordReset" wire:loading.attr="disabled" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 hover:bg-slate-50 transition-colors">
                            Send Password Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Login History Card -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-base font-semibold text-slate-900">Login History</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">When</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">IP</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">User Agent</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse($loginHistory as $h)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-slate-500">{{ $h->logged_in_at?->format('M j, Y H:i') ?? $h->created_at?->format('M j, Y H:i') }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-500 font-mono">{{ $h->ip_address ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-500 truncate max-w-xs">{{ \Illuminate\Support\Str::limit($h->user_agent ?? '—', 50) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-sm text-slate-500">No login history.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                    {{ $loginHistory->links() }}
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="xl:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-base font-semibold text-slate-900">At a Glance</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Role</p>
                        <p class="text-sm font-semibold text-slate-900">{{ str_replace('_', ' ', ucfirst($currentRole)) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Client</p>
                        <p class="text-sm font-semibold text-slate-900">{{ $user->client?->company_name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Last Login</p>
                        <p class="text-sm font-semibold text-slate-900">{{ $user->last_login_at?->format('M j, Y H:i') ?? '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Set Password Modal --}}
    @if($showPasswordModal)
        <div class="modal modal-blur fade show d-block" tabindex="-1" role="dialog" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Set Password</h5>
                        <button type="button" class="btn-close" wire:click="closePasswordModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control @error('newPassword') is-invalid @enderror" wire:model="newPassword" autocomplete="new-password">
                            @error('newPassword') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" class="form-control @error('newPasswordConfirmation') is-invalid @enderror" wire:model="newPasswordConfirmation" autocomplete="new-password">
                            @error('newPasswordConfirmation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="text-muted small">Password must be at least 8 characters.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" wire:click="closePasswordModal">Cancel</button>
                        <button type="button" class="btn btn-warning" wire:click="setPassword" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="setPassword">Set Password</span>
                            <span wire:loading wire:target="setPassword">Saving…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
