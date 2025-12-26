<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">AI Audit Log</h2>
            <div class="text-muted small">Searchable task log with input/output previews, cost, tokens, and quality ratings.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.providers') }}">Providers</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.tasks') }}">Task config</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.usage') }}">Usage</a>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-12 col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" wire:model="status">
                        <option value="">(all)</option>
                        <option value="pending">pending</option>
                        <option value="processing">processing</option>
                        <option value="completed">completed</option>
                        <option value="failed">failed</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Provider</label>
                    <select class="form-select" wire:model="provider">
                        <option value="">(all)</option>
                        @foreach($providers as $p)
                            <option value="{{ $p }}">{{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Task type</label>
                    <select class="form-select" wire:model="taskType">
                        <option value="">(all)</option>
                        @foreach($taskTypes as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Search</label>
                    <input class="form-control" wire:model.debounce.400ms="q" placeholder="task/provider/model...">
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Task</th>
                            <th>Status</th>
                            <th>Provider/Model</th>
                            <th class="text-end">Tokens</th>
                            <th class="text-end">Cost</th>
                            <th>Input preview</th>
                            <th>Output preview</th>
                            <th style="width: 220px;">Quality</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tasks as $t)
                            <tr>
                                <td>#{{ $t->id }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $t->task_type }}</div>
                                    <div class="text-muted small">{{ $t->created_at?->toDateTimeString() }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $t->status === 'completed' ? 'success' : ($t->status === 'failed' ? 'danger' : 'secondary') }}">{{ $t->status }}</span>
                                </td>
                                <td>
                                    <div class="text-uppercase">{{ $t->provider_used ?: '—' }}</div>
                                    <div class="text-muted small">{{ $t->model_used ?: '—' }}</div>
                                </td>
                                <td class="text-end">{{ $t->tokens_used ?? '—' }}</td>
                                <td class="text-end">{{ $t->cost !== null ? '$' . number_format((float)$t->cost, 6) : '—' }}</td>
                                <td class="text-muted small" style="max-width: 280px; white-space: pre-wrap;">{{ $this->previewJson($t->input_data) }}</td>
                                <td class="text-muted small" style="max-width: 320px; white-space: pre-wrap;">{{ $this->previewJson($t->output_data) }}</td>
                                <td>
                                    <div class="d-flex gap-2 align-items-start">
                                        <select class="form-select form-select-sm" wire:model="ratings.{{ $t->id }}" style="width: 90px;">
                                            <option value="">—</option>
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                            <option value="5">5</option>
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary" wire:click="saveRating({{ $t->id }})">Save</button>
                                    </div>
                                    <textarea class="form-control form-control-sm mt-1" rows="2" wire:model="ratingNotes.{{ $t->id }}" placeholder="Notes (optional)"></textarea>
                                </td>
                            </tr>
                        @endforeach
                        @if($tasks->isEmpty())
                            <tr><td colspan="9" class="text-muted p-3">No tasks found.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $tasks->links() }}
        </div>
    </div>
</div>

