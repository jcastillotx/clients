<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-clipboard-check mr-1"></i> Onboarding progress</h3>
    </div>
    <div class="card-body">
        @if(!$workflow)
            <div class="text-muted">No onboarding workflow found yet.</div>
        @else
            <div class="mb-2 d-flex justify-content-between">
                <div class="text-muted">Status: <strong>{{ $workflow->status }}</strong></div>
                <div class="text-muted">{{ $workflow->completion_percentage }}%</div>
            </div>
            <div class="progress mb-3" style="height: 10px;">
                <div class="progress-bar" role="progressbar" style="width: {{ $workflow->completion_percentage }}%"></div>
            </div>

            <div class="list-group">
                @foreach($workflow->tasks as $t)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="font-weight-bold">{{ $t->task_name }}</div>
                            <div class="text-muted small">{{ $t->task_type ?? 'task' }}</div>
                        </div>
                        <span class="badge badge-{{ $t->status === 'completed' ? 'success' : ($t->status === 'blocked' ? 'danger' : 'secondary') }}">
                            {{ $t->status }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

