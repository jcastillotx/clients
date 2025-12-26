<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Prompt Templates</h2>
            <div class="text-muted small">Store and version system prompts. Use {{variable}} placeholders.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.assistant') }}">Assistant chat</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.knowledge-base') }}">Knowledge base</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.workflows') }}">Workflow builder</a>
        </div>
    </div>

    @if(session()->has('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-xl-4">
            <div class="card mb-3">
                <div class="card-header"><div class="card-title mb-0">Templates</div></div>
                <div class="card-body">
                    <div class="mb-2 text-muted small">Select a template to manage versions.</div>
                    <div class="list-group">
                        @foreach($templates as $t)
                            <button class="list-group-item list-group-item-action {{ $templateId === $t->id ? 'active' : '' }}"
                                    wire:click="selectTemplate({{ $t->id }})">
                                <div class="fw-semibold">{{ $t->name }}</div>
                                <div class="text-muted small">{{ $t->key }}</div>
                            </button>
                        @endforeach
                        @if($templates->isEmpty())
                            <div class="text-muted">No templates yet.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><div class="card-title mb-0">{{ $templateId ? 'Edit template' : 'Create template' }}</div></div>
                <div class="card-body">
                    @if(!$templateId)
                        <div class="mb-2">
                            <label class="form-label">Key</label>
                            <input class="form-control" wire:model="key" placeholder="admin_assistant_system">
                        </div>
                    @else
                        <div class="text-muted small mb-2">Key: <span class="fw-semibold">{{ $key }}</span></div>
                    @endif

                    <div class="mb-2">
                        <label class="form-label">Name</label>
                        <input class="form-control" wire:model="name">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" wire:model="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" wire:model="status">
                            <option value="active">active</option>
                            <option value="inactive">inactive</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        @if($templateId)
                            <button class="btn btn-primary" wire:click="saveTemplate">Save</button>
                        @else
                            <button class="btn btn-primary" wire:click="createTemplate">Create</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card mb-3">
                <div class="card-header"><div class="card-title mb-0">Versions</div></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Version</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($versions as $v)
                                    <tr>
                                        <td>v{{ $v->version }}</td>
                                        <td><span class="badge bg-{{ $v->status === 'active' ? 'success' : ($v->status === 'draft' ? 'secondary' : 'dark') }}">{{ $v->status }}</span></td>
                                        <td class="text-muted small">{{ $v->notes }}</td>
                                        <td class="text-end">
                                            @if($v->status !== 'active')
                                                <button class="btn btn-sm btn-outline-success" wire:click="activateVersion({{ $v->id }})">Activate</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                @if($versions->isEmpty())
                                    <tr><td colspan="4" class="text-muted p-3">Select a template to view versions.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Add version</div></div>
                <div class="card-body">
                    @if(!$templateId)
                        <div class="text-muted">Select or create a template first.</div>
                    @else
                        <div class="row g-2">
                            <div class="col-12 col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" wire:model="version_status">
                                    <option value="draft">draft</option>
                                    <option value="active">active</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-9">
                                <label class="form-label">Variables JSON (optional)</label>
                                <input class="form-control font-monospace" wire:model="variables_json" placeholder='{"context_summary":""}'>
                            </div>
                            <div class="col-12">
                                <label class="form-label">System prompt</label>
                                <textarea class="form-control font-monospace" wire:model="system_prompt" rows="10"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <input class="form-control" wire:model="notes">
                            </div>
                        </div>

                        <div class="mt-3">
                            <button class="btn btn-primary" wire:click="addVersion">Create version</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

