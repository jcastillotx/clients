<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Storage</div>
            <h2 class="page-title mb-0">Dropbox Browser</h2>
            <div class="text-muted small">
                Account: <strong>{{ data_get($connection->credentials, 'account_email') ?: '—' }}</strong>
                @if(data_get($connection->credentials, 'folder_path'))
                    · Base folder: <strong>{{ data_get($connection->credentials, 'folder_path') }}</strong>
                @endif
                · Last sync: <strong>{{ $connection->last_synced_at ? $connection->last_synced_at->format('Y-m-d H:i') : '—' }}</strong>
                @if($pendingCount > 0)
                    · Pending: <strong>{{ $pendingCount }}</strong>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.storage.dropbox.connect') }}" class="btn btn-outline-secondary">Connections</a>
            <button class="btn btn-outline-primary" wire:click="syncNow" wire:loading.attr="disabled">Sync now</button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div class="d-flex gap-2 align-items-center">
                    <button class="btn btn-outline-secondary" wire:click="goUp" @disabled($path === '')>
                        Up
                    </button>
                    <div class="text-muted small">Path: <strong>{{ $path === '' ? '/' : '/' . $path }}</strong></div>
                </div>
                <div class="d-flex gap-2">
                    <input type="text" class="form-control" style="min-width: 220px;" placeholder="Search…" wire:model.live.debounce.350ms="search" wire:change="refreshListing">
                    <button class="btn btn-outline-secondary" wire:click="refreshListing" wire:loading.attr="disabled">Refresh</button>
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
                            <th>Modified</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($items as $i)
                            @if(($i['type'] ?? '') === 'folder')
                                <tr>
                                    <td>
                                        <a href="#" wire:click.prevent="openFolder(@js($i['name']))">
                                            📁 {{ $i['name'] }}
                                        </a>
                                    </td>
                                    <td class="text-end text-muted">—</td>
                                    <td class="text-muted">—</td>
                                    <td class="text-end text-muted">—</td>
                                </tr>
                            @elseif(($i['type'] ?? '') === 'file')
                                <tr>
                                    <td class="fw-semibold">{{ $i['name'] }}</td>
                                    <td class="text-end text-muted">{{ number_format(((int)($i['size'] ?? 0)) / 1024, 1) }} KB</td>
                                    <td class="text-muted">{{ !empty($i['modified_at']) ? \Carbon\Carbon::parse($i['modified_at'])->format('Y-m-d H:i') : '—' }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" wire:click="download(@js($i['path']))">View/Download</button>
                                        <button class="btn btn-sm btn-outline-secondary"
                                                wire:click="openLinkModal(@js($i['id']), @js($i['path']), @js($i['name']))">
                                            Link
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" wire:click="delete(@js($i['path']))">Delete</button>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No files found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
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
                    @error('newFolderName') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                    <button class="btn btn-outline-primary mt-3" wire:click="createFolder" wire:loading.attr="disabled">Create</button>
                </div>
            </div>
        </div>
    </div>

    @if($linkModalOpen)
        <div class="modal fade show" style="display:block;" tabindex="-1" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Link file to Document</h5>
                        <button type="button" class="btn-close" wire:click="closeLinkModal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2 text-muted small">File: {{ $linkFileName }}</div>
                        <label class="form-label">Select document</label>
                        <select class="form-select" wire:model.live="linkDocumentId">
                            <option value="">Select…</option>
                            @foreach($documents as $d)
                                <option value="{{ $d->id }}">{{ $d->title }}</option>
                            @endforeach
                        </select>
                        @error('linkDocumentId') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" wire:click="closeLinkModal">Cancel</button>
                        <button type="button" class="btn btn-primary" wire:click="linkToDocument" wire:loading.attr="disabled">Link</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <script>
        window.addEventListener('livewire:init', () => {
            Livewire.on('open-url', ({ url }) => {
                if (!url) return;
                window.open(url, '_blank');
            });
        });
    </script>
</div>

