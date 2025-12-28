<div>
    <h2 class="mb-3">Automation Logs</h2>

    <div class="d-flex justify-content-between mb-3">
        <div class="text-muted">Execution log for debugging + audit trail</div>
        <div>
            <a href="{{ route('admin.automation.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-list mr-1"></i> Rules
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label class="mb-1">Trigger</label>
                    <input class="form-control" wire:model.live.debounce.300ms="trigger" placeholder="e.g. request.created">
                </div>
                <div class="col-md-6">
                    <label class="mb-1">Rule ID</label>
                    <input type="number" class="form-control" wire:model.live.debounce.300ms="ruleId" placeholder="e.g. 12">
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Rule</th>
                            <th>Trigger</th>
                            <th>Matched</th>
                            <th>Result</th>
                            <th class="text-muted">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($runs as $run)
                            <tr>
                                <td class="text-muted">{{ $run->created_at?->toDayDateTimeString() }}</td>
                                <td>
                                    <div class="font-weight-bold">#{{ $run->automation_rule_id }}</div>
                                    <div class="text-muted small">{{ $run->rule?->name }}</div>
                                </td>
                                <td><code>{{ $run->trigger }}</code></td>
                                <td>
                                    <span class="badge badge-{{ $run->matched ? 'info' : 'secondary' }}">
                                        {{ $run->matched ? 'yes' : 'no' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $run->succeeded ? 'success' : 'danger' }}">
                                        {{ $run->succeeded ? 'ok' : 'fail' }}
                                    </span>
                                    @if($run->error)
                                        <div class="text-danger small">{{ $run->error }}</div>
                                    @endif
                                </td>
                                <td class="text-muted">
                                    {{ $run->actions_succeeded }}/{{ $run->actions_total }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted text-center py-4">No runs yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $runs->links() }}
        </div>
    </div>
</div>

