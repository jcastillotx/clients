<x-app-layout>
    <x-slot name="header">Account health</x-slot>

    <div class="card mb-3">
        <div class="card-body">
            <label class="mb-1">Client</label>
            <select class="form-control" wire:model="clientId" wire:change="loadClient">
                <option value="">Select…</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if($client)
        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-heartbeat mr-1"></i> Snapshot</h3>
                    </div>
                    <div class="card-body">
                        <div class="h4 mb-0">{{ $client->company_name }}</div>
                        <div class="text-muted small">AI health score (latest): {{ $snapshot->score ?? '—' }}</div>
                        <div class="text-muted small">Risk: {{ $snapshot->risk_level ?? '—' }}</div>
                        <div class="text-muted small">Computed: {{ $snapshot->computed_at?->toDateTimeString() ?? '—' }}</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-flag-checkered mr-1"></i> Add milestone</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="mb-1">Milestone</label>
                            <input class="form-control" wire:model.defer="milestoneName" placeholder="e.g. Launch new landing page">
                        </div>
                        <div class="form-group">
                            <label class="mb-1">Target date</label>
                            <input type="date" class="form-control" wire:model.defer="milestoneTargetDate">
                        </div>
                        <button class="btn btn-outline-primary" wire:click="addMilestone"><i class="fas fa-plus mr-1"></i> Add</button>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-shield-alt mr-1"></i> Risks & opportunities (JSON)</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="mb-1">Risk factors</label>
                            <textarea class="form-control" rows="6" wire:model.defer="riskFactorsJson"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="mb-1">Opportunities</label>
                            <textarea class="form-control" rows="6" wire:model.defer="opportunitiesJson"></textarea>
                        </div>
                        <button class="btn btn-primary" wire:click="saveAccountHealth"><i class="fas fa-save mr-1"></i> Save</button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list mr-1"></i> Milestones</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Target</th>
                                    <th>Achieved</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($milestones as $m)
                                    <tr>
                                        <td>{{ $m->milestone_name }}</td>
                                        <td>{{ $m->status }}</td>
                                        <td>{{ $m->target_date?->toDateString() ?? '—' }}</td>
                                        <td>{{ $m->achieved_date?->toDateString() ?? '—' }}</td>
                                    </tr>
                                @endforeach
                                @if($milestones->isEmpty())
                                    <tr><td colspan="4" class="text-muted p-3">No milestones yet.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>

