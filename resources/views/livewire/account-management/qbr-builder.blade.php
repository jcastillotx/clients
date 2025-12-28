<div>
    <h2 class="mb-3">QBRs</h2>

    <div class="card mb-3">
        <div class="card-body">
            <label class="mb-1">Client</label>
            <select class="form-control" wire:model="clientId">
                <option value="">Select…</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if($clientId)
        <div class="row">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-plus mr-1"></i> Create QBR</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="mb-1">Scheduled date</label>
                            <input type="date" class="form-control" wire:model.defer="scheduledDate">
                        </div>
                        <div class="form-group">
                            <label class="mb-1">Presentation URL</label>
                            <input class="form-control" wire:model.defer="presentationUrl" placeholder="https://...">
                        </div>
                        <div class="form-group">
                            <label class="mb-1">Notes</label>
                            <textarea class="form-control" rows="4" wire:model.defer="notes"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="mb-1">Action items (JSON)</label>
                            <textarea class="form-control" rows="4" wire:model.defer="actionItemsJson"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="mb-1">Next QBR date</label>
                            <input type="date" class="form-control" wire:model.defer="nextQbrDate">
                        </div>
                        <button class="btn btn-primary" wire:click="create"><i class="fas fa-save mr-1"></i> Save</button>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list mr-1"></i> Recent QBRs</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Scheduled</th>
                                    <th>Next</th>
                                    <th>Presentation</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($qbrs as $q)
                                    <tr>
                                        <td>{{ $q->scheduled_date?->toDateString() ?? '—' }}</td>
                                        <td>{{ $q->next_qbr_date?->toDateString() ?? '—' }}</td>
                                        <td>
                                            @if($q->presentation_url)
                                                <a href="{{ $q->presentation_url }}" target="_blank" rel="noopener">Open</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                @if($qbrs->isEmpty())
                                    <tr><td colspan="3" class="text-muted p-3">No QBRs yet.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

