<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">AI Knowledge Base</h2>
            <div class="text-muted small">Select company documents that the assistant can cite via RAG.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.assistant') }}">Assistant chat</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.prompt-templates') }}">Prompt templates</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.workflows') }}">Workflow builder</a>
        </div>
    </div>

    @if(session()->has('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <label class="form-label">Search documents</label>
            <input class="form-control" wire:model.debounce.400ms="search" placeholder="Title or filename">
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Document</th>
                            <th>Included</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($docs as $d)
                            @php($in = in_array($d->id, $kbIds, true))
                            <tr>
                                <td>#{{ $d->id }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $d->title ?: $d->original_filename }}</div>
                                    <div class="text-muted small">{{ $d->mime_type }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $in ? 'success' : 'secondary' }}">{{ $in ? 'yes' : 'no' }}</span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary" wire:click="toggleInclude({{ $d->id }})">
                                        {{ $in ? 'Remove' : 'Add' }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        @if($docs->isEmpty())
                            <tr><td colspan="4" class="text-muted p-3">No documents found.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $docs->links() }}
        </div>
    </div>
</div>

