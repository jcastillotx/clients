<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Storage</div>
            <h2 class="page-title mb-0">Unified File Browser</h2>
            <div class="text-muted small">Search and manage files across all connected providers.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.storage') }}">Dashboard</a>
            <a class="btn btn-outline-secondary" href="{{ route('admin.storage.settings') }}">Settings</a>
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
            <div class="row g-3">
                @if($isAdmin)
                    <div class="col-12 col-lg-3">
                        <label class="form-label">Client</label>
                        <select class="form-select" wire:model.live="client_id">
                            <option value="">All clients</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-12 col-lg-2">
                    <label class="form-label">Provider</label>
                    <select class="form-select" wire:model.live="provider">
                        <option value="all">All</option>
                        <option value="aws_s3">S3</option>
                        <option value="dropbox">Dropbox</option>
                        <option value="google_drive">Drive</option>
                    </select>
                </div>

                <div class="col-12 col-lg-2">
                    <label class="form-label">Type</label>
                    <select class="form-select" wire:model.live="type">
                        <option value="all">All</option>
                        <option value="pdf">PDF</option>
                        <option value="image">Images</option>
                        <option value="doc">Docs</option>
                        <option value="sheet">Sheets</option>
                        <option value="archive">Archives</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="col-12 col-lg-2">
                    <label class="form-label">From</label>
                    <input type="date" class="form-control" wire:model.live="date_from">
                </div>

                <div class="col-12 col-lg-2">
                    <label class="form-label">To</label>
                    <input type="date" class="form-control" wire:model.live="date_to">
                </div>

                <div class="col-12 col-lg-5">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" placeholder="Filename..." wire:model.live.debounce.350ms="search">
                </div>

                <div class="col-12 col-lg-3 d-flex align-items-end">
                    <label class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" wire:model.live="conflicts_only">
                        <span class="form-check-label">Conflicts only (requires client filter)</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table">
                <thead>
                <tr>
                    <th>File</th>
                    <th>Provider</th>
                    <th>Client</th>
                    <th class="text-end">Size</th>
                    <th>Modified</th>
                    <th>Links</th>
                    <th>Tags</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($files as $f)
                    @php
                        $provider = $f->storageConnection->provider;
                        $providerLabel = match ($provider) {
                            'aws_s3' => 'S3',
                            'dropbox' => 'Dropbox',
                            'google_drive' => 'Drive',
                            default => $provider,
                        };
                        $tags = is_array($f->tags) ? $f->tags : [];
                    @endphp
                    <tr>
                        <td class="fw-semibold">
                            {{ $f->file_name }}
                            <div class="text-muted small">{{ $f->mime_type ?: '—' }}</div>
                        </td>
                        <td><span class="badge bg-secondary">{{ $providerLabel }}</span></td>
                        <td class="text-muted">{{ $f->storageConnection->client?->company_name ?: '—' }}</td>
                        <td class="text-end text-muted">{{ number_format(((int)$f->file_size) / 1024, 1) }} KB</td>
                        <td class="text-muted">{{ $f->last_modified_at ? $f->last_modified_at->format('Y-m-d H:i') : '—' }}</td>
                        <td class="text-muted small">
                            @if($f->document_id) Doc #{{ $f->document_id }} @endif
                            @if($f->request_id) · Req #{{ $f->request_id }} @endif
                            @if($f->contract_id) · Ctr #{{ $f->contract_id }} @endif
                        </td>
                        <td class="text-muted small">
                            @if(!empty($tags))
                                {{ implode(', ', $tags) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-secondary" wire:click="openLinkModal({{ $f->id }})">Link/Share/Tag</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No files found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $files->links() }}
        </div>
    </div>

    @if($linkModalOpen)
        <div class="modal fade show" style="display:block;" tabindex="-1" role="dialog" aria-modal="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Link / share file &amp; tags</h5>
                        <button type="button" class="btn-close" wire:click="closeLinkModal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Link target</label>
                                <select class="form-select" wire:model.live="linkTarget">
                                    <option value="document">Document</option>
                                    <option value="request">Request</option>
                                    <option value="contract">Contract</option>
                                </select>
                            </div>

                            <div class="col-12" @if($linkTarget !== 'document') style="display:none" @endif>
                                <label class="form-label">Document</label>
                                <select class="form-select" wire:model.live="linkDocumentId">
                                    <option value="">—</option>
                                    @foreach($documents as $d)
                                        <option value="{{ $d->id }}">{{ $d->title }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12" @if($linkTarget !== 'request') style="display:none" @endif>
                                <label class="form-label">Request</label>
                                <select class="form-select" wire:model.live="linkRequestId">
                                    <option value="">—</option>
                                    @foreach($requests as $r)
                                        <option value="{{ $r->id }}">#{{ $r->id }} · {{ $r->title }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12" @if($linkTarget !== 'contract') style="display:none" @endif>
                                <label class="form-label">Contract</label>
                                <select class="form-select" wire:model.live="linkContractId">
                                    <option value="">—</option>
                                    @foreach($contracts as $c)
                                        <option value="{{ $c->id }}">#{{ $c->id }} · {{ $c->title }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Tags</label>
                                <input type="text" class="form-control" wire:model.live.debounce.350ms="tagsInput" placeholder="e.g. invoices, Q4, urgent">
                                <div class="text-muted small mt-1">Comma-separated tags.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" wire:click="closeLinkModal">Cancel</button>
                        <button type="button" class="btn btn-primary" wire:click="saveLinksAndTags" wire:loading.attr="disabled">Save</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>

