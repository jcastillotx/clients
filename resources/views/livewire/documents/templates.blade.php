<x-app-layout>
    <x-slot name="header">Document Templates</x-slot>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-alt mr-1"></i> Create Template</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="mb-1">Name</label>
                        <input class="form-control" wire:model.defer="name">
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Category</label>
                        <input class="form-control" wire:model.defer="category" placeholder="nda, proposal, contract...">
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Variables (comma separated)</label>
                        <input class="form-control" wire:model.defer="variables_csv" placeholder="client_name, company_name">
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Body</label>
                        <textarea class="form-control" rows="8" wire:model.defer="body" placeholder="Hello {{client_name}}..."></textarea>
                        <small class="text-muted">Supports {{variable}} replacement. (Full rich template engines can be added later.)</small>
                    </div>
                    <button class="btn btn-primary" wire:click="saveTemplate">
                        <i class="fas fa-save mr-1"></i> Save Template
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
                        <select class="form-control" wire:model.defer="generate_template_id">
                            <option value="">Select template...</option>
                            @foreach($templates as $t)
                                <option value="{{ $t->id }}">#{{ $t->id }} — {{ $t->name }} ({{ $t->category }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Title</label>
                        <input class="form-control" wire:model.defer="generate_title" placeholder="e.g. NDA - Acme Inc">
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Destination</label>
                        <select class="form-control" wire:model.defer="generate_destination">
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
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($templates as $t)
                                    <tr>
                                        <td>{{ $t->id }}</td>
                                        <td>{{ $t->name }}</td>
                                        <td class="text-muted">{{ $t->category }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-muted text-center py-3">No templates yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

