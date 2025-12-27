<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">AI Workflow Builder</h2>
            <div class="text-muted small">Define chainable AI tasks with checkpoints (stored as JSON).</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.assistant') }}">Assistant chat</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.prompt-templates') }}">Prompt templates</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.knowledge-base') }}">Knowledge base</a>
        </div>
    </div>

    @if(session()->has('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session()->has('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Create workflow</div></div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Name</label>
                            <input class="form-control" wire:model="name" placeholder="New request workflow">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" wire:model="status">
                                <option value="inactive">inactive</option>
                                <option value="active">active</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Definition JSON</label>
                            <textarea class="form-control font-monospace" wire:model="definitionJson" rows="12"></textarea>
                            <div class="text-muted small mt-1">
                                This is a minimal builder scaffold. You can store nodes/edges/conditions now; execution can be wired later.
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-primary" wire:click="save" wire:loading.attr="disabled">Save workflow</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Recent workflows</div></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>ID</th><th>Name</th><th>Status</th><th>Created</th></tr></thead>
                            <tbody>
                                @foreach($workflows as $w)
                                    <tr>
                                        <td>#{{ $w->id }}</td>
                                        <td>{{ $w->name }}</td>
                                        <td><span class="badge bg-{{ $w->status === 'active' ? 'success' : 'secondary' }}">{{ $w->status }}</span></td>
                                        <td class="text-muted small">{{ $w->created_at?->toDateTimeString() }}</td>
                                    </tr>
                                @endforeach
                                @if($workflows->isEmpty())
                                    <tr><td colspan="4" class="text-muted p-3">No workflows yet.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

