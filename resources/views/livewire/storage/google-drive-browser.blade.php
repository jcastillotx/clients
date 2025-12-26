<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Storage</div>
            <h2 class="page-title mb-0">Google Drive Browser</h2>
            <div class="text-muted small">
                Account: <strong>{{ $accountEmail ?: '—' }}</strong>
                · Folder: <strong>{{ $folderId }}</strong>
                · Last sync: <strong>{{ $connection->last_synced_at ? $connection->last_synced_at->format('Y-m-d H:i') : '—' }}</strong>
                @if($pendingCount > 0)
                    · Pending: <strong>{{ $pendingCount }}</strong>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.storage.google-drive.connect') }}" class="btn btn-outline-secondary">Connections</a>
            <button class="btn btn-outline-secondary" wire:click="goRoot" wire:loading.attr="disabled">Base folder</button>
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
                <div class="text-muted small">Current folder ID: <strong>{{ $folderId }}</strong></div>
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
                    <div class="card-title mb-0">Folders</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover card-table">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Modified</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($folders as $f)
                            <tr>
                                <td>
                                    <a href="#" wire:click.prevent="openFolder(@js($f['id']))">
                                        📁 {{ $f['name'] }}
                                    </a>
                                    <div class="text-muted small">{{ $f['id'] }}</div>
                                </td>
                                <td class="text-muted">{{ !empty($f['modified_at']) ? \Carbon\Carbon::parse($f['modified_at'])->format('Y-m-d H:i') : '—' }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary" wire:click="openFolder(@js($f['id']))">Open</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No folders found.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
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
                        @forelse($files as $f)
                            @php
                                $isGoogleDoc = str_starts_with(($f['mime_type'] ?? ''), 'application/vnd.google-apps.');
                            @endphp
                            <tr>
                                <td class="fw-semibold">
                                    {{ $f['name'] }}
                                    <div class="text-muted small">{{ $f['mime_type'] ?? '—' }}</div>
                                </td>
                                <td class="text-end text-muted">{{ number_format(((int)($f['size'] ?? 0)) / 1024, 1) }} KB</td>
                                <td class="text-muted">{{ !empty($f['modified_at']) ? \Carbon\Carbon::parse($f['modified_at'])->format('Y-m-d H:i') : '—' }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary" wire:click="download(@js($f['id']))">Download</button>
                                    @if(!empty($f['web_view_link']))
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ $f['web_view_link'] }}" target="_blank">View</a>
                                    @endif
                                    <button class="btn btn-sm btn-outline-secondary"
                                            wire:click="openLinkModal(@js($f['id']), @js($f['name']), @js($f['mime_type']))">
                                        Link
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" wire:click="delete(@js($f['id']))">Delete</button>
                                </td>
                            </tr>
                            @if($isGoogleDoc)
                                <tr class="bg-body-tertiary">
                                    <td colspan="4">
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <div class="text-muted small">Export:</div>
                                            <button class="btn btn-sm btn-outline-primary" wire:click="export(@js($f['id']), 'application/pdf')">PDF</button>
                                            <button class="btn btn-sm btn-outline-secondary" wire:click="export(@js($f['id']), 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')">DOCX</button>
                                            <button class="btn btn-sm btn-outline-secondary" wire:click="export(@js($f['id']), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')">XLSX</button>
                                            <button class="btn btn-sm btn-outline-secondary" wire:click="export(@js($f['id']), 'application/vnd.openxmlformats-officedocument.presentationml.presentation')">PPTX</button>
                                        </div>
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

