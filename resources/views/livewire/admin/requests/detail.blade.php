<div>
    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Request #{{ $request->id }}</h2>
            <div class="text-slate-500 text-sm">
                {{ $request->client?->company_name ?? ('Client #' . $request->client_id) }} ·
                {{ $request->created_at?->format('Y-m-d H:i') }}
            </div>
        </div>
        <div class="flex flex-wrap gap-2 items-center">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-{{ $request->status_color }}-100 text-{{ $request->status_color }}-800">{{ $statusLabels[$request->status] ?? $request->status }}</span>
            <a href="{{ route('admin.requests.estimator', $request) }}" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">Estimator</a>
            <a href="{{ route('admin.requests.index') }}" class="bg-slate-600 text-white px-4 py-2 rounded-lg hover:bg-slate-700">Back</a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
        <div class="lg:col-span-8">
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 mb-3">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <div class="font-semibold text-slate-900">{{ $request->title }}</div>
                </div>
                <div class="p-6">
                    <div class="text-slate-500 mb-2">
                        <strong>Type:</strong> {{ $request->type_label }} ·
                        <strong>Priority:</strong> {{ $request->priority_label }} ·
                        <strong>Created by:</strong> {{ $request->creator?->name ?? '—' }}
                    </div>
                    <div class="prose" style="white-space: pre-wrap;">{{ $request->description }}</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 mb-3">
                <div class="flex justify-content-between items-center px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <div class="font-semibold text-slate-900">Workflow</div>
                    <div class="flex flex-wrap gap-2">
                        <div class="text-slate-500 text-sm">One-click status updates</div>
                        <button class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-slate-600 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100" wire:click="convertToProject">
                            <i class="fas fa-project-diagram mr-1"></i> Convert to project
                        </button>
                    </div>
                </div>
                <div class="p-6 flex flex-wrap gap-2">
                    <button class="inline-flex items-center px-4 py-2 text-sm font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100" wire:click="setStatus('in_review')">Move to Review</button>
                    <button class="btn-brand-primary" wire:click="setStatus('approved')">Approve</button>
                    <button class="inline-flex items-center px-4 py-2 text-sm font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100" wire:click="setStatus('in_progress')">Start Work</button>
                    <button class="inline-flex items-center px-4 py-2 text-sm font-semibold text-green-600 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100" wire:click="setStatus('completed')">Complete</button>
                    <button class="bg-slate-600 text-white px-4 py-2 rounded-lg hover:bg-slate-700" wire:click="setStatus('on_hold')">On Hold</button>
                    <button class="inline-flex items-center px-4 py-2 text-sm font-semibold text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100" wire:click="setStatus('cancelled')">Cancel</button>
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
                        <textarea class="form-control" rows="3" wire:model="newComment" placeholder="Write an update…"></textarea>
                        @error('newComment') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        <label class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" wire:model.live="newCommentInternal">
                            <span class="form-check-label">Internal (staff only)</span>
                        </label>
                        <div class="mt-2">
                            <button class="btn btn-primary" wire:click="addComment" wire:loading.attr="disabled">Post</button>
                        </div>
                    </div>

                    @php
                        $lastClientMsg = optional($comments->firstWhere('is_internal', false))->comment ?? '';
                        $ctx = [
                            'request_id' => $request->id,
                            'title' => $request->title,
                            'type' => $request->type,
                            'priority' => $request->priority,
                            'status' => $request->status,
                        ];
                    @endphp
                    <livewire:communication.smart-reply-box
                        :clientMessage="$lastClientMsg"
                        :contextJson="json_encode($ctx)"
                        :wire:key="'smart-reply-request-'.$request->id"
                    />

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
                    @if($canAssign)
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
                    @else
                        <div class="mb-2">
                            <label class="form-label text-muted">Assigned to</label>
                            <div class="text-body">{{ $request->assignee?->name ?? 'Unassigned' }}</div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-muted">Due date</label>
                            <div class="text-body">{{ $request->due_date?->format('Y-m-d') ?? '—' }}</div>
                        </div>
                        <div class="text-muted small">
                            <i class="fas fa-lock me-1"></i> Only super admins, admins, and project managers can assign requests.
                        </div>
                    @endif
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

