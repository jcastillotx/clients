<div>
    <h2 class="mb-3">Privacy</h2>

    <div class="card mb-3">
        <div class="card-body">
            <div class="h4 mb-1">Data privacy</div>
            <div class="text-muted">Request an export of your data or request account deletion.</div>

            <div class="d-flex flex-wrap mt-3" style="gap:8px;">
                <button class="btn btn-outline-primary" wire:click="requestExport">
                    <i class="fas fa-download mr-1"></i> Request data export
                </button>
                <button class="btn btn-outline-danger" wire:click="requestDeletion">
                    <i class="fas fa-user-slash mr-1"></i> Request deletion
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list mr-1"></i> Requests</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
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
                            <td>{{ $r->type }}</td>
                            <td><span class="badge badge-{{ $r->status === 'processed' ? 'success' : ($r->status === 'rejected' ? 'danger' : 'secondary') }}">{{ $r->status }}</span></td>
                            <td class="text-muted">{{ $r->created_at?->toDateTimeString() }}</td>
                            <td class="text-muted">{{ $r->processed_at?->toDateTimeString() ?? '—' }}</td>
                            <td class="text-right">
                                @if($r->type === 'export' && $r->status === 'processed')
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('privacy.export.download', $r) }}">Download</a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @if($requests->isEmpty())
                        <tr><td colspan="6" class="text-muted p-3">No requests.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

