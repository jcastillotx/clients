<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Proposal Builder</h3>
            @if($proposal)
                <span class="badge badge-secondary">{{ $proposal->status }}</span>
            @endif
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="col-md-6">
                    <label>Request</label>
                    <select class="form-control" wire:model="requestId">
                        <option value="">Select…</option>
                        @foreach($requests as $r)
                            <option value="{{ $r->id }}">#{{ $r->id }} — {{ $r->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button class="btn btn-outline-primary" wire:click="generateFromRequest">Generate from request</button>
                </div>
            </div>

            <hr>

            <div class="form-group">
                <label>Title</label>
                <input class="form-control" wire:model.defer="title">
            </div>
            <div class="form-group">
                <label>Template ID</label>
                <input class="form-control" wire:model.defer="templateId" placeholder="social_media, seo, website, ...">
            </div>

            <div class="form-group">
                <label>Content (JSON)</label>
                <textarea class="form-control" rows="10" wire:model.defer="contentJson"></textarea>
                <small class="text-muted">Includes sections like executive summary, scope, pricing, terms.</small>
            </div>

            <div class="form-group">
                <label>Pricing (JSON)</label>
                <textarea class="form-control" rows="8" wire:model.defer="pricingJson"></textarea>
            </div>

            <div class="d-flex" style="gap: 8px;">
                <button class="btn btn-primary" wire:click="save">Save</button>
                <button class="btn btn-success" wire:click="sendToClient" @if(!$proposal) disabled @endif>Send to client</button>
                @if($proposal)
                    <a class="btn btn-outline-secondary" href="{{ route('admin.proposals.analytics', $proposal) }}">Analytics</a>
                @endif
            </div>
        </div>
    </div>
</div>

