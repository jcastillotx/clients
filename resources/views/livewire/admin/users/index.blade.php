<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Users</h2>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Add User</a>
            <a href="{{ route('admin.users.permissions') }}" class="btn btn-outline-secondary">Permissions</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-12 col-md-5">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" placeholder="Name or email…" wire:model.live.debounce.350ms="search">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">Role</label>
                    <select class="form-select" wire:model.live="role">
                        <option value="all">All</option>
                        @foreach($roles as $r)
                            <option value="{{ $r }}">{{ str_replace('_', ' ', ucfirst($r)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model.live="status">
                        <option value="all">All</option>
                        @foreach($statuses as $s)
                            <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Client</th>
                    <th>Status</th>
                    <th>Last login</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($users as $u)
                    @php
                        $roleNames = $u->roles?->pluck('name')?->values() ?? collect();
                        $roleLabel = $roleNames->first() ?: '—';
                        $status = $u->status ?? ($u->is_active ? 'active' : 'inactive');
                        $statusColor = match($status) {
                            'active' => 'success',
                            'inactive' => 'secondary',
                            'suspended' => 'danger',
                            default => 'secondary'
                        };
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $u->name }}</td>
                        <td class="text-muted">{{ $u->email }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($roleLabel)) }}</td>
                        <td>{{ $u->client?->company_name ?? '—' }}</td>
                        <td><span class="badge bg-{{ $statusColor }}">{{ ucfirst($status) }}</span></td>
                        <td class="text-muted">{{ $u->last_login_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="toggleActive({{ $u->id }})">
                                {{ $u->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $users->links() }}
        </div>
    </div>
</div>

