<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">All Requests</h2>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.requests.create') }}" class="btn btn-primary">Create Request</a>
            <button type="button" class="btn btn-outline-secondary" wire:click="$set('viewMode','kanban')">Kanban</button>
        </div>
    </div>

    <!-- Status Summary Cards -->
    <div class="row row-deck row-cards mb-3">
        @php
            $totalRequests = array_sum($statusCounts ?? []);
            $openRequests = ($statusCounts['pending'] ?? 0) + ($statusCounts['in_review'] ?? 0) + ($statusCounts['approved'] ?? 0) + ($statusCounts['in_progress'] ?? 0) + ($statusCounts['on_hold'] ?? 0);
        @endphp

        <div class="col-6 col-sm-4 col-lg-2">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-primary text-white avatar">
                                <i class="fas fa-list"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">{{ $totalRequests }}</div>
                            <div class="text-muted">Total</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-sm-4 col-lg-2">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-info text-white avatar">
                                <i class="fas fa-spinner"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">{{ $openRequests }}</div>
                            <div class="text-muted">Open</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-sm-4 col-lg-2">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-warning text-white avatar">
                                <i class="fas fa-clock"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">{{ $statusCounts['pending'] ?? 0 }}</div>
                            <div class="text-muted">Pending</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-sm-4 col-lg-2">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-cyan text-white avatar">
                                <i class="fas fa-tasks"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">{{ $statusCounts['in_progress'] ?? 0 }}</div>
                            <div class="text-muted">In Progress</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-sm-4 col-lg-2">
            <div class="card card-sm {{ ($overdueCount ?? 0) > 0 ? 'border-danger' : '' }}">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-danger text-white avatar">
                                <i class="fas fa-exclamation-triangle"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium {{ ($overdueCount ?? 0) > 0 ? 'text-danger' : '' }}">{{ $overdueCount ?? 0 }}</div>
                            <div class="text-muted">Overdue</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-sm-4 col-lg-2">
            <div class="card card-sm {{ ($unassignedCount ?? 0) > 0 ? 'border-warning' : '' }}">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-secondary text-white avatar">
                                <i class="fas fa-user-slash"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium {{ ($unassignedCount ?? 0) > 0 ? 'text-warning' : '' }}">{{ $unassignedCount ?? 0 }}</div>
                            <div class="text-muted">Unassigned</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-12 col-lg-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" placeholder="Title, description, client…" wire:model.live.debounce.350ms="search">
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label">Client (search)</label>
                    <input type="text" class="form-control" placeholder="Start typing…" wire:model.live.debounce.350ms="clientSearch">
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label">Client</label>
                    <select class="form-select" wire:model.live="clientId">
                        <option value="">All clients</option>
                        @foreach($clientOptions as $c)
                            <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label">Assigned to</label>
                    <select class="form-select" wire:model.live="assignedTo">
                        <option value="">Anyone</option>
                        @foreach($staffOptions as $u)
                            <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-lg-3">
                    <label class="form-label">Status (multi)</label>
                    <select class="form-select" multiple size="5" wire:model.live="statuses">
                        @foreach($statusLabels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label">Type (multi)</label>
                    <select class="form-select" multiple size="5" wire:model.live="types">
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label">Priority</label>
                    <select class="form-select" wire:model.live="priority">
                        <option value="">All</option>
                        @foreach($priorities as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-1">
                    <label class="form-label">From</label>
                    <input type="date" class="form-control" wire:model.live="dateFrom">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label">To</label>
                    <input type="date" class="form-control" wire:model.live="dateTo">
                </div>
            </div>
        </div>
    </div>

    @if(!empty($selected))
        <div class="alert alert-info d-flex flex-wrap align-items-center gap-2">
            <div class="me-auto">
                <strong>{{ count($selected) }}</strong> selected
            </div>
            <div class="d-flex flex-wrap gap-2">
                <div class="input-group">
                    <span class="input-group-text">Status</span>
                    <select class="form-select" wire:model="bulkStatus">
                        <option value="">—</option>
                        @foreach($statusLabels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-primary" wire:click="applyBulkStatus">Apply</button>
                </div>

                <div class="input-group">
                    <span class="input-group-text">Assign</span>
                    <select class="form-select" wire:model="bulkAssignedTo">
                        <option value="">—</option>
                        @foreach($staffOptions as $u)
                            <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-primary" wire:click="applyBulkAssign">Apply</button>
                </div>

                <div class="input-group">
                    <span class="input-group-text">Priority</span>
                    <select class="form-select" wire:model="bulkPriority">
                        <option value="">—</option>
                        @foreach($priorities as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-outline-primary" wire:click="applyBulkPriority">Apply</button>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table">
                <thead>
                <tr>
                    <th style="width:1%">
                        <input type="checkbox" class="form-check-input" wire:model="selectPage">
                    </th>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Assigned To</th>
                    <th>Created</th>
                    <th>Due</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($requests as $r)
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input" value="{{ $r->id }}" wire:model="selected">
                        </td>
                        <td class="text-muted">#{{ $r->id }}</td>
                        <td>{{ $r->client?->company_name ?? ('Client #' . $r->client_id) }}</td>
                        <td class="fw-semibold">{{ $r->title }}</td>
                        <td>{{ $types[$r->type] ?? $r->type }}</td>
                        <td>
                            <span class="badge bg-{{ $r->status_color }}">{{ $statusLabels[$r->status] ?? $r->status }}</span>
                        </td>
                        <td>{!! $r->priority_badge !!}</td>
                        <td>{{ $r->assignee?->name ?? '—' }}</td>
                        <td class="text-muted">{{ $r->created_at?->format('Y-m-d') }}</td>
                        <td class="{{ $r->isOverdue() ? 'text-danger fw-semibold' : 'text-muted' }}">
                            {{ $r->due_date?->format('Y-m-d') ?? '—' }}
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.requests.show', $r) }}" class="btn btn-sm btn-outline-primary">Open</a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="openAssign({{ $r->id }})">Assign</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">No requests found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $requests->links() }}
        </div>
    </div>

    @if($showAssign)
        <div class="modal fade show" style="display:block;" tabindex="-1" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Assign Request</h5>
                        <button type="button" class="btn-close" wire:click="$set('showAssign', false)" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Assign to</label>
                                <select class="form-select" wire:model="assignToUserId">
                                    <option value="">Unassigned</option>
                                    @foreach($staffOptions as $u)
                                        <option value="{{ $u['id'] }}">{{ $u['name'] }} ({{ $u['email'] }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Due date</label>
                                <input type="date" class="form-control" wire:model="assignDueDate">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Internal notes</label>
                                <textarea class="form-control" rows="3" placeholder="Visible to staff only…" wire:model="assignInternalNote"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-check">
                                    <input class="form-check-input" type="checkbox" wire:model="assignNotify">
                                    <span class="form-check-label">Email the assigned staff member</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" wire:click="$set('showAssign', false)">Cancel</button>
                        <button type="button" class="btn btn-primary" wire:click="saveAssignment" wire:loading.attr="disabled">Save</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>

