<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Client</div>
            <h2 class="page-title mb-0">AI Assistant</h2>
            <div class="text-muted small">Help with FAQs and portal navigation. For account changes, we’ll escalate to a human.</div>
        </div>
    </div>

    @if($needsHuman)
        <div class="alert alert-warning">
            It looks like you may need a human. Please send a message in the portal and our team will help.
        </div>
    @endif

    @if($error)
        <div class="alert alert-danger">{{ $error }}</div>
    @endif
    @if(session()->has('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body" style="max-height: 60vh; overflow: auto;">
            @forelse($this->messages as $m)
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="fw-semibold text-uppercase">{{ $m['role'] }}</div>
                        <div class="text-muted small">{{ $m['created_at'] ?? '' }}</div>
                    </div>
                    <div class="border rounded p-2 bg-light" style="white-space: pre-wrap;">{{ $m['content'] }}</div>
                    @if($m['role'] === 'assistant')
                        <div class="d-flex gap-2 mt-1">
                            <button class="btn btn-sm btn-outline-success" wire:click="feedback({{ $m['id'] }}, 'up')">👍</button>
                            <button class="btn btn-sm btn-outline-danger" wire:click="feedback({{ $m['id'] }}, 'down')">👎</button>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-muted">No messages yet.</div>
            @endforelse
        </div>
        <div class="card-footer">
            <div class="d-flex gap-2">
                <input class="form-control" wire:model.defer="message" placeholder="Ask a question...">
                <button class="btn btn-primary" wire:click="send" wire:loading.attr="disabled">Send</button>
            </div>
        </div>
    </div>
</div>

