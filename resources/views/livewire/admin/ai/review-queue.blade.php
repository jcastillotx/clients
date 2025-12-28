<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">AI Human Review Queue</h2>
            <div class="text-muted small">Approve or reject AI outputs before they are used in sensitive contexts.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.safety') }}">Safety</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.quality') }}">Quality</a>
        </div>
    </div>

    @if(session()->has('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-12 col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model="status">
                        <option value="">(all)</option>
                        <option value="pending">pending</option>
                        <option value="approved">approved</option>
                        <option value="rejected">rejected</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" wire:model="category">
                        <option value="">(all)</option>
                        @foreach($categories as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Reason</th>
                            <th>Output</th>
                            <th style="width: 320px;">Approved text</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $i)
                            <tr>
                                <td>#{{ $i->id }}</td>
                                <td><span class="badge bg-secondary">{{ $i->category }}</span></td>
                                <td><span class="badge bg-{{ $i->status === 'pending' ? 'warning' : ($i->status === 'approved' ? 'success' : 'danger') }}">{{ $i->status }}</span></td>
                                <td class="text-muted small" style="max-width: 220px; white-space: pre-wrap;">{{ $i->reason }}</td>
                                <td class="text-muted small" style="max-width: 340px; white-space: pre-wrap;">{{ $i->output_preview }}</td>
                                <td>
                                    <textarea class="form-control form-control-sm" rows="4" wire:model="approvedText.{{ $i->id }}"></textarea>
                                </td>
                                <td class="text-end">
                                    @if($i->status === 'pending')
                                        <button class="btn btn-sm btn-outline-success" wire:click="approve({{ $i->id }})">Approve</button>
                                        <button class="btn btn-sm btn-outline-danger" wire:click="reject({{ $i->id }})">Reject</button>
                                    @else
                                        <span class="text-muted small">Reviewed</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        @if($items->isEmpty())
                            <tr><td colspan="7" class="text-muted p-3">No items found.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $items->links() }}
        </div>
    </div>
</div>

