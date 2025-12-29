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

    {{-- Flash Messages --}}
    @include('partials.flash-messages')

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

            <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" wire:model.live="selectPage">
                    <span class="form-check-label">Select first 50 results</span>
                </label>

                @if(count($selected) > 0)
                    <span class="badge bg-primary ms-2">{{ count($selected) }} selected</span>
                @endif

                <div class="ms-auto">
                    <button class="btn btn-outline-danger" type="button" wire:click="confirmBulkDelete" @disabled(empty($selected))>
                        Delete Selected
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0" style="width: 100%;">
                <thead class="table-light">
                <tr>
                    <th style="width: 50px;" class="text-center">
                        <span class="text-muted small">SEL</span>
                    </th>
                    <th style="width: 18%;">NAME</th>
                    <th style="width: 22%;">EMAIL</th>
                    <th style="width: 12%;">ROLE</th>
                    <th style="width: 14%;">CLIENT</th>
                    <th style="width: 8%;" class="text-center">STATUS</th>
                    <th style="width: 14%;">LAST LOGIN</th>
                    <th style="width: 12%;" class="text-center">ACTIONS</th>
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
                        $isSuperAdmin = $roleNames->contains('super_admin');
                        $isSelf = $u->id === auth()->id();
                    @endphp
                    <tr>
                        <td class="text-center align-middle">
                            <input type="checkbox" class="form-check-input" wire:model.live="selected" value="{{ $u->id }}"
                                @if($isSuperAdmin || $isSelf) disabled title="{{ $isSelf ? 'Cannot select yourself' : 'Cannot select super admins' }}" @endif>
                        </td>
                        <td class="align-middle">
                            <span class="fw-semibold">{{ $u->name }}</span>
                        </td>
                        <td class="align-middle text-muted">
                            <span title="{{ $u->email }}">{{ $u->email }}</span>
                        </td>
                        <td class="align-middle">{{ str_replace('_', ' ', ucfirst($roleLabel)) }}</td>
                        <td class="align-middle">
                            @if($u->client)
                                <span title="{{ $u->client->company_name }}">{{ Str::limit($u->client->company_name, 15) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="align-middle text-center">
                            <span class="badge bg-{{ $statusColor }}">{{ ucfirst($status) }}</span>
                        </td>
                        <td class="align-middle text-muted small">
                            {{ $u->last_login_at?->format('M j, Y H:i') ?? '—' }}
                        </td>
                        <td class="align-middle text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="toggleActive({{ $u->id }})" title="{{ $u->is_active ? 'Deactivate user' : 'Activate user' }}">
                                    {{ $u->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-users fa-2x mb-2 d-block"></i>
                            No users found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="card-footer">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- Bulk Delete Confirmation Modal --}}
    @if($showDeleteConfirmModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="cancelBulkDelete"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete <strong>{{ count($selected) }}</strong> selected user(s)?</p>
                        <p class="text-muted mb-0">
                            <small>Super admins and your own account will be skipped. Deleted users can be restored from the database if needed.</small>
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cancelBulkDelete">Cancel</button>
                        <button type="button" class="btn btn-danger" wire:click="bulkDelete">Delete Users</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

