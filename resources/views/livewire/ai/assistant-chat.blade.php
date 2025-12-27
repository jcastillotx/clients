<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">AI Assistant</h2>
            <div class="text-muted small">Multi-turn chat with optional page context + knowledge base RAG.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.providers') }}">AI settings</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.usage') }}">Usage</a>
        </div>
    </div>

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
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#edit_{{ $m['id'] }}">Edit</button>
                            <div class="text-muted small ms-auto">
                                {{ $m['provider_used'] ? strtoupper($m['provider_used']) : '' }}
                                {{ $m['model_used'] ? ' · ' . $m['model_used'] : '' }}
                                {{ $m['cost'] !== null ? ' · $' . number_format((float)$m['cost'], 4) : '' }}
                            </div>
                        </div>
                        <div class="collapse mt-2" id="edit_{{ $m['id'] }}">
                            <div class="border rounded p-2">
                                <div class="text-muted small mb-1">Edits are captured for prompt/training improvements.</div>
                                <textarea class="form-control form-control-sm" rows="3" wire:model.defer="edits.{{ $m['id'] }}"></textarea>
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-primary" wire:click="saveEdit({{ $m['id'] }})">Save edit</button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-muted">No messages yet.</div>
            @endforelse
        </div>
        <div class="card-footer">
            <div class="d-flex gap-2">
                <input class="form-control" wire:model.defer="message" placeholder="Ask about clients, revenue, follow-ups, or say 'Create invoice for ...'">
                <button class="btn btn-primary" wire:click="send" wire:loading.attr="disabled">Send</button>
            </div>
        </div>
    </div>
</div>

