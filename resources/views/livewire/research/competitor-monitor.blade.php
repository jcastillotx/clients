<div>
    <h2 class="mb-3">Competitor monitor</h2>

    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plus mr-1"></i> Add competitor</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="mb-1">Name</label>
                        <input class="form-control" wire:model="competitorName">
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Website</label>
                        <input class="form-control" wire:model="websiteUrl" placeholder="https://example.com">
                    </div>
                    <button class="btn btn-primary" wire:click="addCompetitor"><i class="fas fa-save mr-1"></i> Add</button>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-binoculars mr-1"></i> Competitors</h3>
                </div>
                <div class="card-body">
                    @forelse($competitors as $c)
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <div class="font-weight-bold">{{ $c->competitor_name }}</div>
                                <div class="text-muted small">{{ $c->meta['last_checked_at'] ?? '' }}</div>
                            </div>
                            <div class="text-muted small">{{ $c->website_url }}</div>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary" wire:click="checkNow({{ $c->id }})">
                                    <i class="fas fa-sync mr-1"></i> Check now
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">No competitors yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

