<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Request #{{ $request->id }}</h2>
            <div class="text-muted small">
                {{ $request->client?->company_name ?? ('Client #' . $request->client_id) }} ·
                {{ $request->created_at?->format('Y-m-d H:i') }}
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge bg-{{ $request->status_color }}">{{ $statusLabels[$request->status] ?? $request->status }}</span>
            <a href="{{ route('admin.requests.estimator', $request) }}" class="btn btn-outline-primary">Estimator</a>
            <a href="{{ route('admin.requests.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="card mb-3">
                <div class="card-header">
                    <div class="card-title">{{ $request->title }}</div>
                </div>
                <div class="card-body">
                    <div class="text-muted mb-2">
                        <strong>Type:</strong> {{ $request->type_label }} ·
                        <strong>Priority:</strong> {{ $request->priority_label }} ·
                        <strong>Created by:</strong> {{ $request->creator?->name ?? '—' }}
                    </div>
                    <div class="prose" style="white-space: pre-wrap;">{{ $request->description }}</div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="card-title mb-0">Workflow</div>
                    <div class="text-muted small">One-click status updates</div>
                </div>
                <div class="card-body d-flex flex-wrap gap-2">
                    <button class="btn btn-outline-info" wire:click="setStatus('in_review')">Move to Review</button>
                    <button class="btn btn-outline-primary" wire:click="setStatus('approved')">Approve</button>
                    <button class="btn btn-outline-info" wire:click="setStatus('in_progress')">Start Work</button>
                    <button class="btn btn-outline-success" wire:click="setStatus('completed')">Complete</button>
                    <button class="btn btn-outline-secondary" wire:click="setStatus('on_hold')">On Hold</button>
                    <button class="btn btn-outline-danger" wire:click="setStatus('cancelled')">Cancel</button>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <div class="card-title">Comments</div>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" wire:model.live="showInternal">
                            <span class="form-check-label">Show internal notes</span>
                        </label>
                        <div class="text-muted small">
                            Client-visible comments are always safe to show; internal notes are staff-only.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Add a comment</label>
                        <textarea class="form-control" rows="3" wire:model.defer="newComment" placeholder="Write an update…"></textarea>
                        @error('newComment') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        <label class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" wire:model.live="newCommentInternal">
                            <span class="form-check-label">Internal (staff only)</span>
                        </label>
                        <div class="mt-2">
                            <button class="btn btn-primary" wire:click="addComment" wire:loading.attr="disabled">Post</button>
                        </div>
                    </div>

                    <div class="divide-y">
                        @forelse($comments as $c)
                            <div class="py-3 border-top">
                                <div class="d-flex justify-content-between gap-2">
                                    <div class="fw-semibold">
                                        {{ $c->user?->name ?? 'System' }}
                                        @if($c->is_internal)
                                            <span class="badge bg-secondary ms-1">Internal</span>
                                        @else
                                            <span class="badge bg-success ms-1">Client</span>
                                        @endif
                                    </div>
                                    <div class="text-muted small">{{ $c->created_at?->format('Y-m-d H:i') }}</div>
                                </div>
                                <div class="text-muted" style="white-space: pre-wrap;">{{ $c->comment }}</div>
                            </div>
                        @empty
                            <div class="text-muted">No comments yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <div class="card-title">Attachments</div>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <input type="file" class="form-control" wire:model="files" multiple>
                            @error('files.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <button class="btn btn-outline-primary" wire:click="uploadAttachments" wire:loading.attr="disabled">Upload</button>
                        </div>
                    </div>

                    <div class="list-group">
                        @forelse($request->attachments as $a)
                            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="{{ $a->url }}">
                                <div>
                                    <div class="fw-semibold">{{ $a->original_filename }}</div>
                                    <div class="text-muted small">
                                        {{ $a->human_file_size }} · uploaded by {{ $a->uploader?->name ?? '—' }}
                                    </div>
                                </div>
                                <span class="badge bg-secondary">Download</span>
                            </a>
                        @empty
                            <div class="text-muted">No attachments.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <livewire:admin.requests.a-i-request-analysis :request="$request" />

            <div class="card mb-3">
                <div class="card-header">
                    <div class="card-title">Assignment</div>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label">Assigned to</label>
                        <select class="form-select" wire:model.live="assigned_to">
                            <option value="">Unassigned</option>
                            @foreach($staffOptions as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                        @error('assigned_to') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Due date</label>
                        <input type="date" class="form-control" wire:model.live="due_date">
                        @error('due_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <button class="btn btn-primary" wire:click="saveAssignment" wire:loading.attr="disabled">Save</button>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <div class="card-title">Time Tracking</div>
                </div>
                <div class="card-body">
                    <div class="text-muted small mb-2">
                        Total logged: <strong>{{ number_format((float)($request->actual_hours ?? 0), 2) }}</strong> hours
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Hours</label>
                            <input type="number" step="0.25" min="0" class="form-control" wire:model.live="timeHours" placeholder="1.5">
                            @error('timeHours') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label">When</label>
                            <input type="datetime-local" class="form-control" wire:model.live="timeLoggedAt">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Note</label>
                            <input type="text" class="form-control" wire:model.live.debounce.350ms="timeNote" placeholder="What did you do?">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-outline-primary" wire:click="addTimeEntry" wire:loading.attr="disabled">Add entry</button>
                        </div>
                    </div>

                    <div class="list-group">
                        @forelse($timeEntries as $t)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between gap-2">
                                    <div class="fw-semibold">{{ $t->user?->name ?? '—' }}</div>
                                    <div class="text-muted small">{{ $t->logged_at?->format('Y-m-d H:i') ?? $t->created_at?->format('Y-m-d H:i') }}</div>
                                </div>
                                <div class="text-muted small">{{ number_format((float)$t->hours, 2) }} hours</div>
                                @if($t->note)
                                    <div class="text-muted" style="white-space: pre-wrap;">{{ $t->note }}</div>
                                @endif
                            </div>
                        @empty
                            <div class="text-muted">No time entries.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <div class="card-title">Related Documents</div>
                </div>
                <div class="card-body">
                    <div class="text-muted small mb-2">
                        Storage providers integration is a placeholder; showing latest client documents.
                    </div>
                    <div class="list-group">
                        @forelse($relatedDocuments as $d)
                            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="{{ $d->download_url }}">
                                <div>
                                    <div class="fw-semibold">{{ $d->title }}</div>
                                    <div class="text-muted small">{{ $d->created_at?->format('Y-m-d') }}</div>
                                </div>
                                <span class="badge bg-secondary">Download</span>
                            </a>
                        @empty
                            <div class="text-muted">No documents found.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

