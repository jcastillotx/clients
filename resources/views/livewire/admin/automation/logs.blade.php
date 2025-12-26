<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">Automation Logs</h2>
                <div class="text-muted">Execution history for debugging and audit trail.</div>
            </div>
            <div class="col-auto">
                <a href="{{ route('admin.automation.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-6 col-md-3">
                    <select class="form-select" wire:model="status">
                        <option value="all">All statuses</option>
                        <option value="succeeded">Succeeded</option>
                        <option value="failed">Failed</option>
                        <option value="skipped">Skipped</option>
                        <option value="dry_run">Dry run</option>
                    </select>
                </div>
                <div class="col-6 col-md-4">
                    <select class="form-select" wire:model="trigger">
                        <option value="all">All triggers</option>
                        @foreach($triggers as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-5">
                    <select class="form-select" wire:model="ruleId">
                        <option value="">All rules</option>
                        @foreach($rules as $r)
                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
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
                        <th>ID</th>
                        <th>Rule</th>
                        <th>Trigger</th>
                        <th>Status</th>
                        <th>When</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($logs as $l)
                    <tr>
                        <td class="text-muted">#{{ $l->id }}</td>
                        <td>
                            @if($l->rule)
                                <a href="{{ route('admin.automation.builder', ['rule' => $l->rule->id]) }}">{{ $l->rule->name }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td><code>{{ $l->trigger }}</code></td>
                        <td>
                            @php
                                $badge = match($l->status) {
                                    'succeeded' => 'bg-success',
                                    'failed' => 'bg-danger',
                                    'skipped' => 'bg-secondary',
                                    'dry_run' => 'bg-info',
                                    default => 'bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $badge }}">{{ $l->status }}</span>
                        </td>
                        <td class="text-muted">{{ $l->created_at?->diffForHumans() }}</td>
                        <td class="text-muted">{{ $l->message }}</td>
                    </tr>
                    @if($l->context)
                        <tr>
                            <td colspan="6" class="bg-light">
                                <pre class="mb-0"><code>{{ json_encode($l->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="6" class="text-muted">No logs yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $logs->links() }}
        </div>
    </div>
</div>

