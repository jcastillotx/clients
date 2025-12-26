<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Storage</div>
            <h2 class="page-title mb-0">S3 Browser</h2>
            <div class="text-muted small">
                Bucket: <strong>{{ data_get($connection->credentials, 'bucket') }}</strong>
                @if(data_get($connection->credentials, 'folder_path'))
                    · Prefix: <strong>{{ data_get($connection->credentials, 'folder_path') }}</strong>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.storage.s3.connect') }}" class="btn btn-outline-secondary">Connections</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        @foreach($breadcrumbs as $c)
                            <li class="breadcrumb-item">
                                <a href="#" wire:click.prevent="navigateTo('{{ $c['path'] }}')">{{ $c['label'] }}</a>
                            </li>
                        @endforeach
                    </ol>
                </nav>
                <div class="d-flex gap-2">
                    <input type="text" class="form-control" style="min-width: 220px;" placeholder="Search…" wire:model.live.debounce.350ms="search">
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-7">
            <div class="card">
                <div class="card-header">
                    <div class="card-title mb-0">Files</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover card-table">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th class="text-end">Size</th>
                            <th>Last modified</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($folders as $f)
                            <tr>
                                <td>
                                    <a href="#" wire:click.prevent="navigateTo('{{ trim($path === '' ? $f['name'] : ($path . '/' . $f['name']), '/') }}')">
                                        📁 {{ $f['name'] }}
                                    </a>
                                </td>
                                <td class="text-end text-muted">—</td>
                                <td class="text-muted">—</td>
                                <td class="text-end text-muted">—</td>
                            </tr>
                        @endforeach

                        @forelse($files as $f)
                            <tr>
                                <td class="fw-semibold">{{ $f['name'] }}</td>
                                <td class="text-end text-muted">{{ number_format(((int)($f['size'] ?? 0)) / 1024, 1) }} KB</td>
                                <td class="text-muted">{{ $f['modified_date'] ? \Carbon\Carbon::parse($f['modified_date'])->format('Y-m-d H:i') : '—' }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary" wire:click="download('{{ $f['id'] }}')">Download</button>
                                    <button class="btn btn-sm btn-outline-secondary" wire:click="openLinkModal('{{ $f['id'] }}')">Link</button>
                                    <button class="btn btn-sm btn-outline-danger" wire:click="delete('{{ $f['id'] }}')">Delete</button>
                                </td>
                            </tr>
                        @empty
                            @if(empty($folders))
                                <tr><td colspan="4" class="text-center text-muted py-4">No files found.</td></tr>
                            @endif
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($nextToken)
                    <div class="card-footer">
                        <button class="btn btn-outline-secondary" wire:click="loadMore" wire:loading.attr="disabled">Load more</button>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card mb-3">
                <div class="card-header">
                    <div class="card-title mb-0">Upload</div>
                </div>
                <div class="card-body">
                    <input type="file" class="form-control" wire:model="uploads" multiple>
                    @error('uploads.*') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                    <button class="btn btn-primary mt-3" wire:click="upload" wire:loading.attr="disabled">Upload</button>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title mb-0">Create folder</div>
                </div>
                <div class="card-body">
                    <input type="text" class="form-control" placeholder="Folder name" wire:model.live.debounce.350ms="newFolderName">
                    <button class="btn btn-outline-primary mt-3" wire:click="createFolder" wire:loading.attr="disabled">Create</button>
                </div>
            </div>
        </div>
    </div>

    @if($showLinkModal)
        <div class="modal fade show" style="display:block;" tabindex="-1" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Link file to Document</h5>
                        <button type="button" class="btn-close" wire:click="$set('showLinkModal', false)" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if($linkError)
                            <div class="alert alert-danger">{{ $linkError }}</div>
                        @endif
                        <div class="mb-2 text-muted small">File: {{ $linkFileId }}</div>
                        <label class="form-label">Select document</label>
                        <select class="form-select" wire:model.live="linkDocumentId">
                            <option value="">Select…</option>
                            @foreach($docs as $d)
                                <option value="{{ $d->id }}">{{ $d->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" wire:click="$set('showLinkModal', false)">Cancel</button>
                        <button type="button" class="btn btn-primary" wire:click="linkToDocument" wire:loading.attr="disabled">Link</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>

