<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Edit User</h2>
            <div class="text-muted small">{{ $user->email }}</div>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="card mb-3">
                <div class="card-header">
                    <div class="card-title mb-0">Account</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" wire:model.live.debounce.350ms="name">
                            @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" wire:model.live.debounce.350ms="email">
                            @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" wire:model.live="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                            @error('status') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Role</label>
                            <select class="form-select" wire:model.live="role">
                                @foreach($roles as $r)
                                    <option value="{{ $r }}">{{ str_replace('_', ' ', ucfirst($r)) }}</option>
                                @endforeach
                            </select>
                            @error('role') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">2FA</label>
                            <label class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" wire:model.live="two_factor_enabled">
                                <span class="form-check-label">Enabled</span>
                            </label>
                            <div class="text-muted small mt-1">Flag only (no enforcement yet).</div>
                        </div>

                        @php
                            $downgrade = false;
                            $rank = fn ($r) => match ($r) { 'super_admin' => 4, 'admin' => 3, 'staff' => 2, 'client' => 1, default => 2 };
                            $downgrade = $rank($role) < $rank($currentRole);
                        @endphp

                        @if($downgrade)
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    You are downgrading permissions from <strong>{{ $currentRole }}</strong> to <strong>{{ $role }}</strong>.
                                    <label class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" wire:model.live="confirmRoleDowngrade">
                                        <span class="form-check-label">Confirm role downgrade</span>
                                    </label>
                                </div>
                            </div>
                        @endif

                        @if($role === 'client')
                            <div class="col-12">
                                <div class="hr-text">Client link</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Client</label>
                                <select class="form-select" wire:model.live="client_id">
                                    <option value="">Select a client…</option>
                                    @foreach($clients as $c)
                                        <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                                    @endforeach
                                </select>
                                @error('client_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        @endif

                        @if($role === 'staff' || $role === 'client')
                            <div class="col-12">
                                <div class="hr-text">{{ $role === 'staff' ? 'Staff assignments' : 'Portal permissions' }}</div>
                            </div>
                        @endif

                        @if($role === 'staff')
                            <div class="col-12 col-md-6">
                                <label class="form-label">Assigned clients</label>
                                <label class="form-label">Assignment role</label>
                                <select class="form-select mb-2" wire:model.live="staffAssignmentRole">
                                    <option value="account_manager">Account manager</option>
                                    <option value="project_lead">Project lead</option>
                                </select>
                                <select class="form-select" multiple size="8" wire:model.live="assignedClientIds">
                                    @foreach($clients as $c)
                                        <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Manual permissions</label>
                                <div class="border rounded p-2" style="max-height: 300px; overflow:auto;">
                                    @foreach($permissionGroups as $group => $perms)
                                        @if(empty($perms)) @continue @endif
                                        <div class="mb-2">
                                            <div class="fw-semibold">{{ $group }}</div>
                                            @foreach($perms as $p)
                                                <label class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="{{ $p }}" wire:model.live="directPermissions">
                                                    <span class="form-check-label">{{ $p }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                                <div class="text-muted small mt-2">For staff, these are direct overrides in addition to role permissions.</div>
                            </div>
                        @endif

                        @if($role === 'client')
                            <div class="col-12 col-md-6">
                                <div class="alert alert-info">
                                    <div class="fw-semibold">Automatic permissions</div>
                                    <div class="text-muted small">
                                        Client portal permissions are also granted automatically based on the client’s enabled features (and paid invoice items that enable features).
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Manual permissions</label>
                                <div class="border rounded p-2" style="max-height: 300px; overflow:auto;">
                                    @foreach($permissionGroups as $group => $perms)
                                        @if(empty($perms)) @continue @endif
                                        <div class="mb-2">
                                            <div class="fw-semibold">{{ $group }}</div>
                                            @foreach($perms as $p)
                                                <label class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="{{ $p }}" wire:model.live="directPermissions">
                                                    <span class="form-check-label">{{ $p }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                                <div class="text-muted small mt-2">Stored as manual overrides; effective permissions = manual + entitlements.</div>
                            </div>
                        @endif

                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-primary" wire:click="save" wire:loading.attr="disabled">Save</button>
                            <button type="button" class="btn btn-outline-secondary" wire:click="sendPasswordReset" wire:loading.attr="disabled">Send password reset</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title mb-0">Login history</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                        <tr>
                            <th>When</th>
                            <th>IP</th>
                            <th>User agent</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($loginHistory as $h)
                            <tr>
                                <td class="text-muted">{{ $h->logged_in_at?->format('Y-m-d H:i') ?? $h->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="text-muted">{{ $h->ip_address ?? '—' }}</td>
                                <td class="text-muted">{{ \Illuminate\Support\Str::limit($h->user_agent ?? '—', 80) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No login history.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{ $loginHistory->links() }}
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card">
                <div class="card-header">
                    <div class="card-title mb-0">At a glance</div>
                </div>
                <div class="card-body">
                    <div><strong>Role:</strong> {{ $currentRole }}</div>
                    <div><strong>Client:</strong> {{ $user->client?->company_name ?? '—' }}</div>
                    <div><strong>Last login:</strong> {{ $user->last_login_at?->format('Y-m-d H:i') ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

