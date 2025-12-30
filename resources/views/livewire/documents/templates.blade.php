<div>
    <h2 class="mb-3">Document Templates</h2>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-alt mr-1"></i> {{ $editing_template_id ? 'Edit Template' : 'Create Template' }}</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="mb-1">Name</label>
                        <input class="form-control" wire:model="name">
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Category</label>
                        <input class="form-control" wire:model="category" placeholder="nda, proposal, contract...">
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Variables (comma separated)</label>
                        <input class="form-control" wire:model="variables_csv" placeholder="client_name, company_name">
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Body</label>
                        <textarea class="form-control" rows="8" wire:model="body" placeholder="Hello @{{client_name}}..."></textarea>
                        <small class="text-muted">Supports @{{variable}} replacement. (Full rich template engines can be added later.)</small>
                    </div>
                    <button class="btn btn-primary" wire:click="saveTemplate">
                        <i class="fas fa-save mr-1"></i> Save Template
                    </button>
                    @if($editing_template_id)
                        <button class="btn btn-outline-secondary ml-2" wire:click="cancelEdit">
                            Cancel
                        </button>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-robot mr-1"></i> Request Template from AI</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="mb-1">Describe the template you want</label>
                        <textarea class="form-control" rows="4" wire:model="ai_request_prompt" placeholder="e.g. Draft a one-page DPA tailored for SaaS vendors, include data security and subprocessors."></textarea>
                        <small class="text-muted">AI will draft a template and load it into the editor for review.</small>
                    </div>
                    <button class="btn btn-outline-primary" wire:click="requestTemplateFromAi" @if($ai_request_loading) disabled @endif>
                        <i class="fas fa-magic mr-1"></i> Generate Draft
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-magic mr-1"></i> Generate Document</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="mb-1">Client</label>
                        <select class="form-control" wire:model.live="generate_client_id">
                            <option value="">Select client...</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Template</label>
                        <select class="form-control" wire:model="generate_template_id">
                            <option value="">Select template...</option>
                            @foreach($templates as $t)
                                <option value="{{ $t->id }}">#{{ $t->id }} — {{ $t->name }} ({{ $t->category }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Title</label>
                        <input class="form-control" wire:model="generate_title" placeholder="e.g. NDA - Acme Inc">
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Destination</label>
                        <select class="form-control" wire:model="generate_destination">
                            <option value="local">Local (documents disk)</option>
                            @foreach($connections as $c)
                                <option value="{{ $c['value'] }}">{{ $c['label'] }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Provider disks must be configured in filesystem config.</small>
                    </div>
                    <button class="btn btn-success" wire:click="generate">
                        <i class="fas fa-play mr-1"></i> Generate
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-1"></i> Templates</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($templates as $t)
                                    <tr>
                                        <td>{{ $t->id }}</td>
                                        <td>{{ $t->name }}</td>
                                        <td class="text-muted">{{ $t->category }}</td>
                                        <td class="text-right">
                                            <button class="btn btn-xs btn-outline-primary" wire:click="editTemplate({{ $t->id }})">
                                                Edit
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted text-center py-3">No templates yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
