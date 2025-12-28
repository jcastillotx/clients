<div>
    <h2 class="mb-3">Automation Rules</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between mb-3">
        <div class="text-muted">Trigger → Conditions → Actions</div>
        <div>
            <a href="{{ route('admin.automation.builder') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> New Automation
            </a>
            <a href="{{ route('admin.automation.logs') }}" class="btn btn-outline-secondary">
                <i class="fas fa-history mr-1"></i> Logs
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;">On</th>
                            <th>Name</th>
                            <th>Trigger</th>
                            <th class="text-muted">Sort</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rules as $r)
                            <tr>
                                <td>
                                    <button class="btn btn-sm btn-{{ $r->is_active ? 'success' : 'secondary' }}" wire:click="toggle({{ $r->id }})">
                                        {{ $r->is_active ? 'ON' : 'OFF' }}
                                    </button>
                                </td>
                                <td>
                                    <div class="font-weight-bold">{{ $r->name }}</div>
                                    <div class="text-muted small">{{ $r->description }}</div>
                                </td>
                                <td><code>{{ $r->trigger }}</code></td>
                                <td class="text-muted">{{ $r->sort_order }}</td>
                                <td class="text-right">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.automation.builder', ['rule' => $r->id]) }}">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a class="btn btn-sm btn-outline-info" href="{{ route('admin.automation.logs', ['ruleId' => $r->id]) }}">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger" wire:click="delete({{ $r->id }})" onclick="return confirm('Delete this automation?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted text-center py-4">No automations yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

