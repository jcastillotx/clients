<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Architecture Advisor</h2>
            <div class="text-muted small">Architecture review, debugging, and stack recommendations.</div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" wire:click="reviewArchitecture" wire:loading.attr="disabled">Review architecture</button>
            <button type="button" class="btn btn-outline-secondary" wire:click="recommendStack" wire:loading.attr="disabled">Recommend stack</button>
            <button type="button" class="btn btn-outline-secondary" wire:click="debug" wire:loading.attr="disabled">Debug logs</button>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card mb-3">
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
                            <label class="form-label">System design / architecture document</label>
                            <textarea class="form-control font-monospace" rows="10" wire:model.live="designDoc"></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Requirements JSON (for tech stack)</label>
                            <textarea class="form-control font-monospace" rows="8" wire:model.live="requirementsJson"></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Error logs</label>
                            <textarea class="form-control font-monospace" rows="8" wire:model.live="errorLogs" placeholder="Paste stack traces / logs here..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card mb-3">
                <div class="card-header">
                    <div class="card-title mb-0">Architecture review output</div>
                </div>
                <div class="card-body">
                    @if($architecture)
                        <pre class="bg-light p-2 rounded small mb-0" style="max-height: 260px; overflow:auto;">{{ json_encode($architecture, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                    @else
                        <div class="text-muted">Run an architecture review to see risks and recommendations.</div>
                    @endif
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <div class="card-title mb-0">Tech stack recommendation</div>
                </div>
                <div class="card-body">
                    @if($stack)
                        <pre class="bg-light p-2 rounded small mb-0" style="max-height: 260px; overflow:auto;">{{ json_encode($stack, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                    @else
                        <div class="text-muted">Recommend a stack to compare options for budget/team/scale.</div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title mb-0">Debug assistant output</div>
                </div>
                <div class="card-body">
                    @if($debug)
                        <pre class="bg-light p-2 rounded small mb-0" style="max-height: 260px; overflow:auto;">{{ json_encode($debug, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                    @else
                        <div class="text-muted">Paste logs and run “Debug logs”.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

