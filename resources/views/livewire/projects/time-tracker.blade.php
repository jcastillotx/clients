<x-app-layout>
    <x-slot name="header">Time tracking</x-slot>

    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-stopwatch mr-1"></i> Timer</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="mb-1">Request</label>
                        <select class="form-control" wire:model="requestId">
                            <option value="">Select…</option>
                            @foreach($requests as $r)
                                <option value="{{ $r->id }}">#{{ $r->id }} — {{ $r->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Task (optional)</label>
                        <select class="form-control" wire:model="taskId">
                            <option value="">None</option>
                            @foreach($tasks as $t)
                                <option value="{{ $t->id }}">{{ $t->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Description</label>
                        <input class="form-control" wire:model.defer="description">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" wire:model.defer="isBillable" id="billable">
                        <label class="form-check-label" for="billable">Billable</label>
                    </div>

                    <div class="d-flex flex-wrap" style="gap: 8px;">
                        <button class="btn btn-success" wire:click="start"><i class="fas fa-play mr-1"></i> Start</button>
                        <button class="btn btn-danger" wire:click="stop" @if(!$running) disabled @endif><i class="fas fa-stop mr-1"></i> Stop</button>
                    </div>

                    @if($running)
                        <div class="alert alert-info mt-3 mb-0">
                            Running since {{ $running->started_at?->toDateTimeString() }} (Request #{{ $running->request_id }})
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-pen mr-1"></i> Manual entry</h3>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="col">
                            <label class="mb-1">Date</label>
                            <input type="date" class="form-control" wire:model.defer="manualDate">
                        </div>
                        <div class="col">
                            <label class="mb-1">Minutes</label>
                            <input type="number" class="form-control" wire:model.defer="manualMinutes">
                        </div>
                    </div>
                    <button class="btn btn-outline-primary mt-3" wire:click="addManual"><i class="fas fa-plus mr-1"></i> Add manual entry</button>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clock mr-1"></i> Recent entries</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Start</th>
                                <th>Minutes</th>
                                <th>Request</th>
                                <th>Billable</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recent as $e)
                                <tr>
                                    <td>{{ $e->started_at?->toDateTimeString() }}</td>
                                    <td>{{ $e->duration_minutes ?? '—' }}</td>
                                    <td>#{{ $e->request_id ?? '—' }}</td>
                                    <td>{{ $e->is_billable ? 'yes' : 'no' }}</td>
                                </tr>
                            @endforeach
                            @if($recent->isEmpty())
                                <tr><td colspan="4" class="text-muted p-3">No time entries yet.</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

