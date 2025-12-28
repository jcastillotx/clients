<div>
    <h2 class="mb-3">Renewals</h2>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-sync-alt mr-1"></i> Contracts expiring soon</h3>
            <div class="card-tools">
                <select class="form-control form-control-sm" wire:model="days">
                    <option value="30">30 days</option>
                    <option value="60">60 days</option>
                    <option value="90">90 days</option>
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Contract</th>
                        <th>End date</th>
                        <th>Days</th>
                        <th>Renewal stage</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contracts as $c)
                        <tr>
                            <td>{{ $c->client?->company_name }}</td>
                            <td>{{ $c->title }}</td>
                            <td>{{ $c->end_date?->toDateString() }}</td>
                            <td>{{ $c->days_until_expiration ?? $c->daysUntilExpiration() }}</td>
                            <td class="text-muted">{{ $c->meta['renewal_stage'] ?? '—' }}</td>
                            <td><button class="btn btn-sm btn-outline-primary" wire:click="edit({{ $c->id }})">Edit</button></td>
                        </tr>
                    @endforeach
                    @if($contracts->isEmpty())
                        <tr><td colspan="6" class="text-muted p-3">No contracts expiring in this window.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    @if($editingId)
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-1"></i> Renewal notes</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="mb-1">Stage</label>
                    <input class="form-control" wire:model.defer="renewalStage" placeholder="e.g. outreach, negotiating, renewed">
                </div>
                <div class="form-group">
                    <label class="mb-1">Notes</label>
                    <textarea class="form-control" rows="4" wire:model.defer="renewalNotes"></textarea>
                </div>
                <button class="btn btn-primary" wire:click="save"><i class="fas fa-save mr-1"></i> Save</button>
            </div>
        </div>
    @endif
</div>

