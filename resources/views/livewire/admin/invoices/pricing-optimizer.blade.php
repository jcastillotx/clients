<div class="card mb-3">
    <div class="card-header">
        <div class="card-title mb-0">Pricing Optimizer</div>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="optimize" wire:loading.attr="disabled">
                Optimize pricing
            </button>
            @if($editable && $pricing && !empty($pricing['suggested_discount']['amount']))
                <button type="button" class="btn btn-primary btn-sm" wire:click="applySuggestedDiscount" wire:loading.attr="disabled">
                    Apply discount (${{ number_format((float)$pricing['suggested_discount']['amount'], 2) }})
                </button>
            @endif
        </div>

        @if($pricing)
            <pre class="bg-light p-2 rounded small mb-0" style="max-height: 260px; overflow:auto;">{{ json_encode($pricing, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
        @else
            <div class="text-muted small">Run optimization to see recommendations.</div>
        @endif
    </div>
</div>

