<div>
    <div class="page-pretitle">Security</div>
    <h2 class="page-title">Privacy requests</h2>
    <div class="text-muted mb-3">GDPR-style export/delete requests.</div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Processed</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $r)
                        <tr>
                            <td>#{{ $r->id }}</td>
                            <td>{{ $r->user?->name ?? '—' }} <div class="text-muted small">{{ $r->user?->email ?? '' }}</div></td>
                            <td>{{ $r->type }}</td>
                            <td><span class="badge bg-{{ $r->status === 'processed' ? 'success' : ($r->status === 'rejected' ? 'danger' : 'secondary') }}">{{ $r->status }}</span></td>
                            <td class="text-muted">{{ $r->created_at?->toDateTimeString() }}</td>
                            <td class="text-muted">{{ $r->processed_at?->toDateTimeString() ?? '—' }}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" wire:click="process({{ $r->id }})" @if($r->status !== 'pending') disabled @endif>
                                    Process
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    @if($requests->isEmpty())
                        <tr><td colspan="7" class="text-muted p-3">No requests.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

