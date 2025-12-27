<x-app-layout>
    <x-slot name="header">Time approvals</x-slot>

    <div class="card mb-3">
        <div class="card-body">
            <div class="form-row">
                <div class="col-md-5">
                    <label class="mb-1">User</label>
                    <select class="form-control" wire:model="userId">
                        <option value="">All</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="mb-1">Week start (Mon)</label>
                    <input type="date" class="form-control" wire:model="weekStart">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-danger w-100" wire:click="lockWeek" @if(!$userId || $isLocked) disabled @endif>
                        <i class="fas fa-lock mr-1"></i> Lock week
                    </button>
                </div>
            </div>
            @if($isLocked)
                <div class="alert alert-warning mt-2 mb-0">
                    Week is locked
                    @if($lockRow?->locked_at)
                        ({{ $lockRow->locked_at->toDateTimeString() }})
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-clock mr-1"></i> Entries ({{ $ws->toDateString() }})</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Start</th>
                        <th>Min</th>
                        <th>Request</th>
                        <th>Task</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $e)
                        <tr>
                            <td>{{ $e->user?->name }}</td>
                            <td class="text-muted">{{ $e->started_at?->toDateTimeString() }}</td>
                            <td>{{ $e->duration_minutes ?? '—' }}</td>
                            <td>#{{ $e->request_id ?? '—' }}</td>
                            <td>{{ $e->task?->title ?? '—' }}</td>
                            <td>
                                <span class="badge badge-secondary">{{ $e->status }}</span>
                                @if($e->approved_at)
                                    <div class="text-muted small">by {{ $e->approver?->name ?? '—' }}</div>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="d-flex justify-content-end flex-wrap" style="gap:6px;">
                                    <button class="btn btn-sm btn-outline-success" wire:click="approve({{ $e->id }})" @if($e->status !== 'pending') disabled @endif>
                                        Approve
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" wire:click="markBilled({{ $e->id }})" @if($e->status !== 'approved') disabled @endif>
                                        Mark billed
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    @if($entries->isEmpty())
                        <tr><td colspan="7" class="text-muted p-3">No entries.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>

