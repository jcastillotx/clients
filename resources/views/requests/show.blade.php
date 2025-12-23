<x-app-layout>
    <x-slot name="header">Request: {{ $request->title }}</x-slot>

    <div class="row">
        <div class="col-lg-8">
            <!-- Request Details -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Request Details</h3>
                    <div class="card-tools">
                        <span class="badge badge-{{ $request->status_color }} mr-2">
                            {{ $request->status_label }}
                        </span>
                        <span class="badge badge-{{ $request->priority_color }}">
                            {{ $request->priority_label }} Priority
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <strong>Type:</strong> {{ $request->type_label }}
                        </div>
                        <div class="col-md-6">
                            <strong>Created:</strong> {{ $request->created_at->format('M d, Y h:i A') }}
                        </div>
                    </div>

                    @if($request->due_date)
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <strong>Due Date:</strong> 
                            <span class="{{ $request->isOverdue() ? 'text-danger font-weight-bold' : '' }}">
                                {{ $request->due_date->format('M d, Y') }}
                                @if($request->isOverdue())
                                <small>(Overdue)</small>
                                @endif
                            </span>
                        </div>
                        @if($request->assignee)
                        <div class="col-md-6">
                            <strong>Assigned To:</strong> {{ $request->assignee->name }}
                        </div>
                        @endif
                    </div>
                    @endif

                    <hr>

                    <h5>Description</h5>
                    <div class="p-3 bg-light rounded">
                        {!! nl2br(e($request->description)) !!}
                    </div>
                </div>
            </div>

            <!-- Attachments -->
            @if($request->attachments->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-paperclip mr-1"></i>
                        Attachments ({{ $request->attachments->count() }})
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($request->attachments as $attachment)
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center p-2 border rounded">
                                <i class="fas fa-file fa-2x text-muted mr-3"></i>
                                <div class="flex-grow-1">
                                    <div class="font-weight-bold">{{ $attachment->original_filename }}</div>
                                    <small class="text-muted">{{ $attachment->human_file_size }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Comments -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-comments mr-1"></i>
                        Comments
                    </h3>
                </div>
                <div class="card-body">
                    <livewire:requests.request-comments :request="$request" />
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Actions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Actions</h3>
                </div>
                <div class="card-body">
                    <a href="{{ route('requests.index') }}" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Requests
                    </a>
                    @if($request->isOpen())
                    <a href="{{ route('requests.edit', $request) }}" class="btn btn-outline-primary btn-block">
                        <i class="fas fa-edit mr-1"></i> Edit Request
                    </a>
                    @endif
                </div>
            </div>

            <!-- Timeline -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Timeline</h3>
                </div>
                <div class="card-body">
                    <div class="timeline timeline-inverse">
                        <div class="time-label">
                            <span class="bg-primary">{{ $request->created_at->format('M d, Y') }}</span>
                        </div>
                        <div>
                            <i class="fas fa-plus bg-success"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    <i class="far fa-clock"></i> {{ $request->created_at->format('h:i A') }}
                                </span>
                                <h3 class="timeline-header">Request Created</h3>
                                <div class="timeline-body">
                                    Created by {{ $request->creator?->name ?? 'Unknown' }}
                                </div>
                            </div>
                        </div>
                        @if($request->started_at)
                        <div>
                            <i class="fas fa-play bg-info"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    <i class="far fa-clock"></i> {{ $request->started_at->format('h:i A') }}
                                </span>
                                <h3 class="timeline-header">Work Started</h3>
                            </div>
                        </div>
                        @endif
                        @if($request->completed_at)
                        <div>
                            <i class="fas fa-check bg-success"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    <i class="far fa-clock"></i> {{ $request->completed_at->format('h:i A') }}
                                </span>
                                <h3 class="timeline-header">Request Completed</h3>
                            </div>
                        </div>
                        @endif
                        <div>
                            <i class="far fa-clock bg-gray"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
