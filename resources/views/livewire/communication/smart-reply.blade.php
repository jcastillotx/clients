<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="card-title mb-0">Smart replies</div>
        <button class="btn btn-sm btn-outline-primary" wire:click="suggest">Suggest</button>
    </div>
    <div class="card-body">
        <div class="text-muted small mb-2">Paste the message you’re replying to, then click Suggest.</div>
        <textarea class="form-control mb-2" rows="3" wire:model.defer="clientMessage" placeholder="Client message…"></textarea>
        <textarea class="form-control mb-2" rows="2" wire:model.defer="contextJson" placeholder="Optional context JSON (request/invoice/project)"></textarea>

        @if($recommendedTone)
            <div class="text-muted small mb-2">Recommended tone: <strong>{{ ucfirst($recommendedTone) }}</strong></div>
        @endif

        <div class="d-flex flex-wrap gap-2">
            @foreach($replies as $i => $r)
                <button class="btn btn-outline-secondary" wire:click="choose({{ $i }})" title="Click to insert">
                    {{ $r['title'] }}
                </button>
            @endforeach
        </div>

        @if(!empty($replies))
            <div class="mt-2 text-muted small">Click a button to insert the reply into the composer.</div>
        @endif
    </div>
</div>

