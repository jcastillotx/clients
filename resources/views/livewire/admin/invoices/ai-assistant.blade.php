<div class="card mb-3">
    <div class="card-header">
        <div class="card-title mb-0">AI Assistant</div>
    </div>

    <div class="card-body">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="generateFromRequest" wire:loading.attr="disabled">
                Generate line items
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="runReview" wire:loading.attr="disabled">
                Review invoice
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="predictPayment" wire:loading.attr="disabled">
                Predict payment
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="suggestPlan" wire:loading.attr="disabled">
                Payment plan
            </button>
        </div>

        @if($generated)
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-semibold">Generated line items</div>
                    @if($editable)
                        <button type="button" class="btn btn-sm btn-primary" wire:click="applyGenerated" wire:loading.attr="disabled">
                            Apply to invoice
                        </button>
                    @endif
                </div>
                <pre class="bg-light p-2 rounded small mb-0" style="max-height: 220px; overflow:auto;">{{ json_encode($generated, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        @endif

        @if($review)
            <div class="mb-3">
                <div class="fw-semibold mb-2">Review results</div>
                <pre class="bg-light p-2 rounded small mb-0" style="max-height: 220px; overflow:auto;">{{ json_encode($review, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        @endif

        @if($prediction)
            <div class="mb-3">
                <div class="fw-semibold mb-2">Payment prediction</div>
                <pre class="bg-light p-2 rounded small mb-0" style="max-height: 220px; overflow:auto;">{{ json_encode($prediction, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        @endif

        @if($paymentPlan)
            <div class="mb-3">
                <div class="fw-semibold mb-2">Payment plan suggestion</div>
                <pre class="bg-light p-2 rounded small mb-0" style="max-height: 220px; overflow:auto;">{{ json_encode($paymentPlan, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        @endif

        <hr>

        <div class="mb-2 fw-semibold">Dispute resolution</div>
        <div class="mb-2">
            <label class="form-label">Client dispute message</label>
            <textarea class="form-control" rows="3" wire:model.live.debounce.300ms="disputeText" placeholder="Paste the client's dispute message here..."></textarea>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" wire:click="draftDisputeResponse" wire:loading.attr="disabled">
            Draft response
        </button>

        @if($dispute)
            <div class="mt-3">
                <pre class="bg-light p-2 rounded small mb-0" style="max-height: 220px; overflow:auto;">{{ json_encode($dispute, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        @endif

        <div class="text-muted small mt-3">
            Notes: AI output may be routed to the human review queue depending on safety rules and invoice amount.
        </div>
    </div>
</div>

