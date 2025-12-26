<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Automation Rules</h2>
                <div class="text-muted">Trigger → Conditions → Actions. Includes execution logs for audit and debugging.</div>
            </div>
            <div class="col-auto">
                <a href="{{ route('admin.automation.builder') }}" class="btn btn-primary">New automation</a>
                <a href="{{ route('admin.automation.logs') }}" class="btn btn-outline-secondary">View logs</a>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <input class="form-control" placeholder="Search automations…" wire:model.debounce.300ms="search">
                </div>
                <div class="col-6 col-md-3">
                    <select class="form-select" wire:model="trigger">
                        <option value="all">All triggers</option>
                        @foreach($triggers as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select class="form-select" wire:model="status">
                        <option value="all">All statuses</option>
                        <option value="active">Active</option>
                        <option value="disabled">Disabled</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table card-table table-vcenter">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Trigger</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Last ran</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rules as $r)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $r->name }}</div>
                            @if($r->description)
                                <div class="text-muted small">{{ $r->description }}</div>
                            @endif
                        </td>
                        <td><code>{{ $r->trigger }}</code></td>
                        <td>{{ $r->run_order }}</td>
                        <td>
                            @if($r->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Disabled</span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $r->last_ran_at?->diffForHumans() ?? '—' }}</td>
                        <td class="text-end">
                            <div class="btn-list flex-nowrap">
                                <a class="btn btn-sm" href="{{ route('admin.automation.builder', ['rule' => $r->id]) }}">Edit</a>
                                <button class="btn btn-sm btn-outline-secondary" wire:click="toggle({{ $r->id }})">
                                    {{ $r->is_active ? 'Disable' : 'Enable' }}
                                </button>
                                <button class="btn btn-sm btn-outline-danger" wire:click="delete({{ $r->id }})"
                                    onclick="return confirm('Delete this automation?')">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">No automation rules yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $rules->links() }}
        </div>
    </div>
</div>

