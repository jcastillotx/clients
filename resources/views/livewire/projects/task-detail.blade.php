<x-app-layout>
    <x-slot name="header">Task</x-slot>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small">Request #{{ $task->request_id }}</div>
                    <div class="h4 mb-0">{{ $task->title }}</div>
                </div>
                <div class="d-flex flex-wrap" style="gap:8px;">
                    <a class="btn btn-outline-secondary" href="{{ route('admin.projects.board') }}"><i class="fas fa-columns mr-1"></i> Board</a>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.projects.timeline') }}"><i class="fas fa-stream mr-1"></i> Timeline</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-edit mr-1"></i> Details</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="mb-1">Title</label>
                        <input class="form-control" wire:model.defer="title">
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Description</label>
                        <textarea class="form-control" rows="4" wire:model.defer="description"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="col-md-4">
                            <label class="mb-1">Status</label>
                            <select class="form-control" wire:model.defer="status">
                                <option value="todo">To do</option>
                                <option value="in_progress">In progress</option>
                                <option value="blocked">Blocked</option>
                                <option value="done">Done</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="mb-1">Priority</label>
                            <select class="form-control" wire:model.defer="priority">
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="mb-1">Assignee</label>
                            <select class="form-control" wire:model.defer="assignedTo">
                                <option value="">Unassigned</option>
                                @foreach($assignees as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-row mt-2">
                        <div class="col-md-4">
                            <label class="mb-1">Start</label>
                            <input type="date" class="form-control" wire:model.defer="startDate">
                        </div>
                        <div class="col-md-4">
                            <label class="mb-1">Due</label>
                            <input type="date" class="form-control" wire:model.defer="dueDate">
                        </div>
                        <div class="col-md-4">
                            <label class="mb-1">Estimate (hrs)</label>
                            <input class="form-control" wire:model.defer="estimatedHours" placeholder="e.g. 4">
                        </div>
                    </div>

                    <div class="form-group mt-2">
                        <label class="mb-1">Depends on</label>
                        <select class="form-control" wire:model.defer="dependsOnTaskId">
                            <option value="">None</option>
                            @foreach($otherTasks as $ot)
                                <option value="{{ $ot->id }}">#{{ $ot->id }} — {{ $ot->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button class="btn btn-primary" wire:click="save"><i class="fas fa-save mr-1"></i> Save</button>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-comment mr-1"></i> Comments</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="mb-1">Add comment</label>
                        <textarea class="form-control" rows="3" wire:model.defer="newComment"></textarea>
                        <label class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" wire:model.defer="newCommentInternal">
                            <span class="form-check-label">Internal</span>
                        </label>
                        <button class="btn btn-outline-primary mt-2" wire:click="addComment"><i class="fas fa-plus mr-1"></i> Add</button>
                    </div>

                    <div class="divide-y">
                        @forelse($comments as $c)
                            <div class="py-2 border-top">
                                <div class="d-flex justify-content-between">
                                    <div class="font-weight-bold">
                                        {{ $c->user?->name ?? 'System' }}
                                        <span class="badge badge-{{ $c->is_internal ? 'secondary' : 'success' }} ml-1">{{ $c->is_internal ? 'internal' : 'client' }}</span>
                                    </div>
                                    <div class="text-muted small">{{ $c->created_at?->format('Y-m-d H:i') }}</div>
                                </div>
                                <div style="white-space: pre-wrap;">{{ $c->comment }}</div>
                            </div>
                        @empty
                            <div class="text-muted">No comments yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-paperclip mr-1"></i> Attachments</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <input type="file" class="form-control" wire:model="upload">
                        <button class="btn btn-outline-primary mt-2" wire:click="uploadAttachment" @if(!$upload) disabled @endif>
                            Upload
                        </button>
                    </div>
                    <div class="list-group">
                        @forelse($attachments as $a)
                            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="{{ $a->download_url }}" target="_blank" rel="noopener">
                                <div>
                                    <div class="font-weight-bold">{{ $a->filename }}</div>
                                    <div class="text-muted small">{{ $a->uploader?->name ?? '—' }}</div>
                                </div>
                                <span class="badge badge-secondary">Download</span>
                            </a>
                        @empty
                            <div class="text-muted">No attachments.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

