<x-app-layout>
    <x-slot name="header">Proposal builder</x-slot>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title"><i class="fas fa-file-signature mr-1"></i> Proposal Builder</h3>
            @if($proposal)
                <span class="badge badge-secondary">{{ $proposal->status }}</span>
            @endif
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="col-md-6">
                    <label class="mb-1">Request</label>
                    <select class="form-control" wire:model="requestId">
                        <option value="">Select…</option>
                        @foreach($requests as $r)
                            <option value="{{ $r->id }}">#{{ $r->id }} — {{ $r->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" wire:click="generateFromRequest">
                        <i class="fas fa-wand-magic-sparkles mr-1"></i> Generate from request
                    </button>
                </div>
            </div>

            <hr>

            <div class="form-group">
                <label class="mb-1">Title</label>
                <input class="form-control" wire:model.defer="title">
            </div>
            <div class="form-group">
                <label class="mb-1">Template ID</label>
                <input class="form-control" wire:model.defer="templateId" placeholder="social_media, seo, website, ...">
            </div>

            <div class="form-group">
                <label class="mb-1">Content (JSON)</label>
                <textarea class="form-control" rows="10" wire:model.defer="contentJson"></textarea>
                <small class="text-muted">This is stored as JSON so templates + sections are extensible.</small>
            </div>

            <div class="form-group">
                <label class="mb-1">Pricing (JSON)</label>
                <textarea class="form-control" rows="8" wire:model.defer="pricingJson"></textarea>
            </div>

            <div class="d-flex flex-wrap" style="gap: 8px;">
                <button class="btn btn-primary" wire:click="save"><i class="fas fa-save mr-1"></i> Save</button>
                <button class="btn btn-success" wire:click="sendToClient" @if(!$proposal) disabled @endif>
                    <i class="fas fa-paper-plane mr-1"></i> Send to client
                </button>
                @if($proposal)
                    <a class="btn btn-outline-secondary" href="{{ route('admin.proposals.analytics', $proposal) }}">
                        <i class="fas fa-chart-bar mr-1"></i> Analytics
                    </a>
                    <a class="btn btn-outline-secondary" href="{{ route('client.proposals.view', $proposal) }}">
                        <i class="fas fa-eye mr-1"></i> Preview (client)
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

