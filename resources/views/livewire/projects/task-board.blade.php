<div>
    <h2 class="mb-3">Task board</h2>

    <div class="card mb-3">
        <div class="card-body">
            <div class="form-row">
                <div class="col-md-8">
                    <label class="mb-1">Request</label>
                    <select class="form-control" wire:model="requestId">
                        <option value="">Select…</option>
                        @foreach($requests as $r)
                            <option value="{{ $r->id }}">#{{ $r->id }} — {{ $r->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" wire:click="seedFromEstimate" @if(!$requestId) disabled @endif>
                        <i class="fas fa-seedling mr-1"></i> Seed from estimate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach(['todo' => 'To do', 'in_progress' => 'In progress', 'blocked' => 'Blocked', 'done' => 'Done'] as $key => $label)
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-columns mr-1"></i> {{ $label }}</h3>
                    </div>
                    <div class="card-body">
                        @foreach($board[$key] as $t)
                            <div class="border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between">
                                    <div class="font-weight-bold">{{ $t->title }}</div>
                                    <a class="btn btn-xs btn-outline-secondary" href="{{ route('admin.projects.tasks.show', $t) }}">Open</a>
                                </div>
                                @if($t->description)
                                    <div class="text-muted small">{{ $t->description }}</div>
                                @endif
                                <div class="text-muted small mt-1">
                                    @if($t->start_date) Start: {{ $t->start_date->toDateString() }} · @endif
                                    @if($t->due_date) Due: {{ $t->due_date->toDateString() }} @endif
                                </div>
                                <div class="mt-2 d-flex flex-wrap" style="gap: 6px;">
                                    @foreach(['todo','in_progress','blocked','done'] as $s)
                                        @if($s !== $key)
                                            <button class="btn btn-sm btn-outline-secondary" wire:click="moveTask({{ $t->id }}, '{{ $s }}')">{{ $s }}</button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                        @if(empty($board[$key]))
                            <div class="text-muted">No tasks.</div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-plus mr-1"></i> Add task</h3>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="col-md-4">
                    <label class="mb-1">Title</label>
                    <input class="form-control" wire:model="newTitle">
                </div>
                <div class="col-md-6">
                    <label class="mb-1">Description</label>
                    <input class="form-control" wire:model="newDescription">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100" wire:click="addTask" @if(!$requestId) disabled @endif>Add</button>
                </div>
            </div>
        </div>
    </div>
</div>

