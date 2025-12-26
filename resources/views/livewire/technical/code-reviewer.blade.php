<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Code Reviewer</h2>
            <div class="text-muted small">AI code review, security audit, and documentation.</div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" wire:click="review" wire:loading.attr="disabled">Run review</button>
            <button type="button" class="btn btn-outline-secondary" wire:click="generateDocs" wire:loading.attr="disabled">Generate docs</button>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <div class="card-title mb-0">Inputs</div>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Provider</label>
                            <select class="form-select" wire:model.live="provider">
                                <option value="claude">Claude</option>
                                <option value="openai">OpenAI</option>
                                <option value="openrouter">OpenRouter</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Model (optional)</label>
                            <input type="text" class="form-control" wire:model.live="model" placeholder="e.g. claude-3-5-sonnet, gpt-4o">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Context JSON (optional)</label>
                            <textarea class="form-control" rows="6" wire:model.live="contextJson"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Code files JSON</label>
                            <div class="text-muted small mb-1">
                                Format: <code>[{"path":"...","language":"php","content":"..."}]</code>
                            </div>
                            <textarea class="form-control font-monospace" rows="14" wire:model.live="codeFilesJson"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card mb-3">
                <div class="card-header">
                    <div class="card-title mb-0">Review output</div>
                </div>
                <div class="card-body">
                    @if($result)
                        <pre class="bg-light p-2 rounded small mb-0" style="max-height: 420px; overflow:auto;">{{ json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                    @else
                        <div class="text-muted">Run a review to see findings.</div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title mb-0">Documentation output</div>
                </div>
                <div class="card-body">
                    @if($docs)
                        <pre class="bg-light p-2 rounded small mb-0" style="max-height: 420px; overflow:auto;">{{ json_encode($docs, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                    @else
                        <div class="text-muted">Generate docs to see README/API doc drafts and comment suggestions.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

