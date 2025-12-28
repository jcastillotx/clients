<div>
    <h2 class="mb-3">Smart Document Browser</h2>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <input class="form-control" placeholder="Search..." wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-2">
                    <select class="form-control" wire:model.live="provider">
                        <option value="">All providers</option>
                        <option value="local">Local</option>
                        <option value="s3">S3</option>
                        <option value="dropbox">Dropbox</option>
                        <option value="drive">Drive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input class="form-control" placeholder="Type (pdf, image, text...)" wire:model.live="fileType">
                </div>
                <div class="col-md-2">
                    <select class="form-control" wire:model.live="linkedEntity">
                        <option value="">Any link</option>
                        <option value="request">Linked to Request</option>
                        <option value="invoice">Linked to Invoice</option>
                        <option value="contract">Linked to Contract</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-control" wire:model.live="tagId">
                        <option value="">All tags</option>
                        @foreach($tags as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <hr>

            <div class="d-flex flex-wrap" style="gap: 8px;">
                <div class="input-group" style="max-width: 420px;">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Link</span>
                    </div>
                    <select class="form-control" wire:model.defer="bulkLinkType">
                        <option value="request">Request</option>
                        <option value="invoice">Invoice</option>
                        <option value="contract">Contract</option>
                    </select>
                    <input type="number" class="form-control" wire:model.defer="bulkLinkId" placeholder="Target ID">
                    <div class="input-group-append">
                        <button class="btn btn-outline-primary" wire:click="bulkLink">Apply</button>
                    </div>
                </div>

                <div class="input-group" style="max-width: 320px;">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Tag</span>
                    </div>
                    <select class="form-control" wire:model.defer="bulkTagId">
                        <option value="">Select tag...</option>
                        @foreach($tags as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" wire:click="bulkTag">Apply</button>
                    </div>
                </div>

                <button class="btn btn-outline-secondary" wire:click="clearSelection">
                    Clear selection ({{ count($selected) }})
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-layer-group mr-1"></i> All sources</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>File</th>
                            <th>Source</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Modified</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $it)
                            @php
                                $isSelected = in_array($it->key, $selected, true);
                                $icon = match($it->provider) {
                                    's3' => 'fas fa-cloud',
                                    'dropbox' => 'fab fa-dropbox',
                                    'drive' => 'fab fa-google-drive',
                                    default => 'fas fa-hdd',
                                };
                            @endphp
                            <tr class="{{ $isSelected ? 'table-info' : '' }}">
                                <td>
                                    <input type="checkbox" @checked($isSelected) wire:click="toggleSelect('{{ $it->key }}')">
                                </td>
                                <td>
                                    <div class="font-weight-bold">{{ $it->title }}</div>
                                    <div class="text-muted small">{{ $it->filename }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-secondary"><i class="{{ $icon }} mr-1"></i> {{ strtoupper($it->provider) }}</span>
                                </td>
                                <td class="text-muted">{{ $it->mime_type }}</td>
                                <td>{{ number_format($it->size_bytes / 1024, 2) }} KB</td>
                                <td class="text-muted">{{ $it->modified_at ? \Carbon\Carbon::parse($it->modified_at)->diffForHumans() : '—' }}</td>
                                <td class="text-right">
                                    @if($it->item_type === 'document')
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('documents.show', $it->id) }}"><i class="fas fa-eye"></i></a>
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('documents.download', $it->id) }}"><i class="fas fa-download"></i></a>
                                        <a class="btn btn-sm btn-outline-info" href="{{ route('documents.viewer.document', [$it->id, 'office']) }}" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i></a>
                                        <a class="btn btn-sm btn-outline-info" href="{{ route('documents.workflow', $it->id) }}"><i class="fas fa-tasks"></i></a>
                                    @else
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('storage.files.download', $it->id) }}"><i class="fas fa-download"></i></a>
                                        <a class="btn btn-sm btn-outline-info" href="{{ route('documents.viewer.storage-file', [$it->id, 'office']) }}" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i></a>
                                    @endif
                                    <button class="btn btn-sm btn-outline-success" wire:click="createShare('{{ $it->key }}')">
                                        <i class="fas fa-share-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted text-center py-4">No results.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($items->hasPages())
            <div class="card-footer">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</div>

