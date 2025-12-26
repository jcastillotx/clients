<x-app-layout>
    <x-slot name="header">Unified File Browser</x-slot>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <input class="form-control" placeholder="Search filename or path..." wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-2">
                    <select class="form-control" wire:model.live="provider">
                        <option value="">All providers</option>
                        @foreach($connections->groupBy('provider') as $prov => $rows)
                            <option value="{{ $prov }}">{{ strtoupper($prov) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input class="form-control" placeholder="File type (pdf, image, video...)" wire:model.live="fileType">
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" wire:model.live="dateFrom">
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control" wire:model.live="dateTo">
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-folder-open mr-1"></i> Files</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>File</th>
                                    <th>Provider</th>
                                    <th>Size</th>
                                    <th>Modified</th>
                                    <th>Tags</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($files as $file)
                                    <tr class="{{ $selectedFileId === $file->id ? 'table-info' : '' }}">
                                        <td>
                                            <div class="font-weight-bold">{{ $file->filename }}</div>
                                            <div class="text-muted small">{{ $file->path }}</div>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary">
                                                {{ strtoupper($file->connection->provider) }}
                                                @if($file->connection->is_primary) • Primary @endif
                                            </span>
                                        </td>
                                        <td>{{ $file->human_size }}</td>
                                        <td>{{ $file->modified_at ? $file->modified_at->diffForHumans() : '—' }}</td>
                                        <td>
                                            @foreach($file->tags as $t)
                                                <span class="badge badge-light">{{ $t->name }}</span>
                                            @endforeach
                                        </td>
                                        <td class="text-right">
                                            <button class="btn btn-sm btn-outline-primary" wire:click="selectFile({{ $file->id }})">
                                                <i class="fas fa-link"></i>
                                            </button>
                                            @if($file->download_url)
                                                <a class="btn btn-sm btn-outline-secondary" href="{{ $file->download_url }}">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-muted text-center py-4">No files found. Run a sync from the Storage Dashboard.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($files->hasPages())
                    <div class="card-footer">
                        {{ $files->links() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-tools mr-1"></i> Selected File Actions</h3>
                </div>
                <div class="card-body">
                    @if(!$selectedFileId)
                        <div class="text-muted">Select a file from the list to tag or link it.</div>
                    @else
                        <div class="form-group">
                            <label class="mb-1">Add tag</label>
                            <div class="input-group">
                                <input class="form-control" wire:model.defer="newTagName" placeholder="e.g. invoice, branding">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-success" wire:click="addTagToSelected">Add</button>
                                </div>
                            </div>
                            @if($tags->count())
                                <small class="text-muted">Existing tags: {{ $tags->pluck('name')->implode(', ') }}</small>
                            @endif
                        </div>

                        <hr>

                        <div class="form-group">
                            <label class="mb-1">Link to</label>
                            <select class="form-control" wire:model.defer="linkType">
                                <option value="request">Request</option>
                                <option value="document">Document</option>
                                <option value="contract">Contract</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="mb-1">Target ID</label>
                            <input type="number" class="form-control" wire:model.defer="linkId" placeholder="Enter ID">
                            <small class="text-muted">Use the existing ID from the app (e.g. Request #123).</small>
                        </div>
                        <div class="form-group">
                            <label class="mb-1">Purpose</label>
                            <input class="form-control" wire:model.defer="linkPurpose" placeholder="reference / attachment">
                        </div>
                        <button class="btn btn-primary" wire:click="linkSelected">
                            <i class="fas fa-paperclip mr-1"></i> Link File
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

