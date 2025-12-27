<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Tools</div>
            <h2 class="page-title mb-0">Technical Advisor</h2>
            <div class="text-muted small">Claude for architecture + stack recommendations.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('research.assistant') }}">Research assistant</a>
            <a class="btn btn-outline-primary" href="{{ route('research.monitor') }}">Industry monitor</a>
        </div>
    </div>

    @if($error)
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-12 col-md-4">
                    <label class="form-label">Mode</label>
                    <select class="form-select" wire:model="mode">
                        <option value="architecture">Architecture review</option>
                        <option value="stack">Technology recommendations</option>
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label">Input JSON</label>
                <textarea class="form-control font-monospace" wire:model="inputJson" rows="10"></textarea>
                <div class="text-muted small mt-1">Paste technical specs / requirements as JSON (or plain text).</div>
            </div>

            <div class="mt-3">
                <button class="btn btn-primary" wire:click="run" wire:loading.attr="disabled">Run</button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title mb-0">Output</div></div>
        <div class="card-body">
            <pre class="bg-light p-3 rounded mb-0" style="white-space: pre-wrap;">{{ json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>
</div>

