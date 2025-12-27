<x-app-layout>
    <x-slot name="header">Upsell tracker</x-slot>

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

    @if($clientId)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-hand-holding-usd mr-1"></i> Opportunities (JSON)</h3>
            </div>
            <div class="card-body">
                <div class="text-muted small mb-2">Track upsell/cross-sell ideas, stage, and value.</div>
                <textarea class="form-control" rows="10" wire:model.defer="opportunitiesJson"></textarea>
                <button class="btn btn-primary mt-2" wire:click="save"><i class="fas fa-save mr-1"></i> Save</button>
            </div>
        </div>
    @endif
</x-app-layout>

