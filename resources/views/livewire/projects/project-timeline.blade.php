<div class="container-fluid">
    <div class="card mb-3">
        <div class="card-body">
            <label>Request</label>
            <select class="form-control" wire:model="requestId">
                <option value="">Select…</option>
                @foreach($requests as $r)
                    <option value="{{ $r->id }}">#{{ $r->id }} — {{ $r->title }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Timeline</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Status</th>
                        <th>Due</th>
                        <th>Est (hrs)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tasks as $t)
                        <tr>
                            <td>{{ $t->title }}</td>
                            <td>{{ $t->status }}</td>
                            <td>{{ $t->due_date?->toDateString() ?? '—' }}</td>
                            <td>{{ $t->estimated_hours ?? '—' }}</td>
                        </tr>
                    @endforeach
                    @if($tasks->isEmpty())
                        <tr><td colspan="4" class="text-muted p-3">No tasks.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

