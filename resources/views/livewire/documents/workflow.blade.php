<div>
    <h2 class="mb-3">Document Workflow: {{ $document->title }}</h2>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file mr-1"></i>
                        Status: <span class="badge badge-{{ $document->status === 'approved' ? 'success' : ($document->status === 'rejected' ? 'danger' : ($document->status === 'pending_review' ? 'warning' : 'secondary')) }}">{{ strtoupper($document->status) }}</span>
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('documents.download', $document) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download mr-1"></i> Download
                        </a>
                        <a href="{{ route('documents.view', $document) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-eye mr-1"></i> View
                        </a>
                        <a href="{{ route('documents.viewer.document', [$document, 'office']) }}" class="btn btn-sm btn-outline-info" target="_blank" rel="noopener">
                            <i class="fas fa-external-link-alt mr-1"></i> Open (Office)
                        </a>
                        <a href="{{ route('documents.viewer.document', [$document, 'google']) }}" class="btn btn-sm btn-outline-info" target="_blank" rel="noopener">
                            <i class="fas fa-external-link-alt mr-1"></i> Open (Google)
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">{{ $document->description }}</p>
                    <div class="text-muted">Client: <strong>{{ $document->client->company_name }}</strong></div>
                    <div class="text-muted">Uploaded: <strong>{{ optional($document->created_at)->toDayDateTimeString() }}</strong></div>
                    <div class="text-muted">Current version: <strong>v{{ $document->current_version }}</strong></div>

                    <hr>

                    <div class="d-flex flex-wrap" style="gap: 8px;">
                        @if(auth()->user()->isClient())
                            @if($document->status === 'pending_review')
                                <button class="btn btn-success" wire:click="approve">
                                    <i class="fas fa-check mr-1"></i> Approve
                                </button>
                                <button class="btn btn-danger" wire:click="reject">
                                    <i class="fas fa-times mr-1"></i> Request changes (reject)
                                </button>
                            @endif
                        @else
                            @if($document->status === 'draft' || $document->status === 'rejected')
                                <button class="btn btn-warning" wire:click="submitForReview">
                                    <i class="fas fa-paper-plane mr-1"></i> Submit for Review
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-comments mr-1"></i> Comments</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <textarea class="form-control" rows="2" wire:model="commentBody" placeholder="Add a comment..."></textarea>
                    </div>
                    @if(!auth()->user()->isClient())
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="internal" wire:model="commentInternal">
                            <label class="custom-control-label" for="internal">Internal (admin only)</label>
                        </div>
                    @endif
                    <button class="btn btn-primary btn-sm" wire:click="addComment">
                        <i class="fas fa-plus mr-1"></i> Post
                    </button>

                    <hr>

                    @forelse($comments as $c)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>{{ $c->user?->name ?? 'System' }}</strong>
                                    @if($c->is_internal)
                                        <span class="badge badge-secondary ml-1">internal</span>
                                    @endif
                                </div>
                                <div class="text-muted">{{ $c->created_at?->diffForHumans() }}</div>
                            </div>
                            <div>{{ $c->body }}</div>
                        </div>
                    @empty
                        <div class="text-muted">No comments yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-code-branch mr-1"></i> Versioning</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="mb-1">Upload new version</label>
                        <input type="file" class="form-control" wire:model="newVersionUpload">
                        <small class="text-muted">Uploading resets status back to Draft.</small>
                    </div>
                    <button class="btn btn-outline-primary btn-sm" wire:click="uploadNewVersion">
                        <i class="fas fa-upload mr-1"></i> Upload
                    </button>

                    <hr>

                    @include('livewire.documents.version-history', ['versions' => $versions])
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-link mr-1"></i> Linking</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        Link this document or any cloud file to Requests / Invoices / Contracts.
                    </div>

                    <div class="form-group">
                        <label class="mb-1">Target type</label>
                        <select class="form-control" wire:model="linkTargetType">
                            <option value="request">Request</option>
                            <option value="invoice">Invoice</option>
                            <option value="contract">Contract</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Target ID</label>
                        <input type="number" class="form-control" wire:model="linkTargetId" placeholder="e.g. 123">
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Purpose</label>
                        <input class="form-control" wire:model="linkPurpose" placeholder="supporting / amendment / addendum">
                    </div>

                    @if(!auth()->user()->isClient())
                        <button class="btn btn-outline-primary btn-sm" wire:click="linkToEntity">
                            <i class="fas fa-link mr-1"></i> Link this document
                        </button>
                    @endif

                    <hr>

                    <div class="form-group">
                        <label class="mb-1">Or link a cloud file</label>
                        <select class="form-control" wire:model="linkStorageFileId">
                            <option value="">Select file...</option>
                            @foreach($availableStorageFiles as $sf)
                                <option value="{{ $sf->id }}">[{{ strtoupper($sf->connection->provider) }}] {{ $sf->filename }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if(!auth()->user()->isClient())
                        <button class="btn btn-outline-secondary btn-sm" wire:click="linkStorageFileToEntity">
                            <i class="fas fa-cloud mr-1"></i> Link cloud file
                        </button>
                    @endif

                    <hr>

                    <div class="text-muted mb-2">Existing links</div>
                    @forelse($links as $l)
                        <div class="border rounded p-2 mb-2">
                            <div class="text-muted small">{{ class_basename($l->linkable_type) }} #{{ $l->linkable_id }}</div>
                            <div><strong>{{ $l->purpose ?? 'linked' }}</strong></div>
                        </div>
                    @empty
                        <div class="text-muted">No links yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-not-equal mr-1"></i> Compare Versions</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="mb-1">Version A</label>
                        <select class="form-control" wire:model="compareA">
                            <option value="">Select...</option>
                            @foreach($versions as $v)
                                <option value="{{ $v->id }}">v{{ $v->version }} — {{ $v->original_filename }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Version B</label>
                        <select class="form-control" wire:model="compareB">
                            <option value="">Select...</option>
                            @foreach($versions as $v)
                                <option value="{{ $v->id }}">v{{ $v->version }} — {{ $v->original_filename }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-outline-primary btn-sm" wire:click="compareVersions">
                        <i class="fas fa-search mr-1"></i> Compare
                    </button>

                    @if($compareTextA !== null || $compareTextB !== null)
                        <hr>
                        @if(!empty($diffA) && !empty($diffB))
                            <div class="row">
                                <div class="col-6">
                                    <div class="text-muted small">A</div>
                                    <div style="max-height: 240px; overflow:auto;" class="border rounded p-2">
                                        @foreach($diffA as $line)
                                            <div style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; white-space: pre-wrap; {{ $line['changed'] ? 'background: #fff3cd;' : '' }}">
                                                <span class="text-muted" style="display:inline-block; width: 36px;">{{ $line['n'] }}</span>{{ $line['text'] }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">B</div>
                                    <div style="max-height: 240px; overflow:auto;" class="border rounded p-2">
                                        @foreach($diffB as $line)
                                            <div style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; white-space: pre-wrap; {{ $line['changed'] ? 'background: #fff3cd;' : '' }}">
                                                <span class="text-muted" style="display:inline-block; width: 36px;">{{ $line['n'] }}</span>{{ $line['text'] }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted">Line-level highlighting (simple alignment).</small>
                        @else
                            <div class="row">
                                <div class="col-6">
                                    <div class="text-muted small">A</div>
                                    <pre style="max-height: 240px; overflow:auto;" class="border rounded p-2">{{ $compareTextA }}</pre>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">B</div>
                                    <pre style="max-height: 240px; overflow:auto;" class="border rounded p-2">{{ $compareTextB }}</pre>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

