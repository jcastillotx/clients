<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Tools</div>
            <h2 class="page-title mb-0">Industry Monitor</h2>
            <div class="text-muted small">Create a monitor and run updates on demand (Perplexity-backed).</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('research.assistant') }}">Research assistant</a>
            <a class="btn btn-outline-primary" href="{{ route('research.technical') }}">Technical advisor</a>
        </div>
    </div>

    @if($error)
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-header"><div class="card-title mb-0">Create monitor</div></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <label class="form-label">Title</label>
                    <input class="form-control" wire:model="title" placeholder="e.g. SaaS trends">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Cadence</label>
                    <select class="form-select" wire:model="cadence">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Industry</label>
                    <input class="form-control" wire:model="industry" placeholder="e.g. Healthcare, Ecommerce, SaaS">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Region (optional)</label>
                    <input class="form-control" wire:model="region" placeholder="e.g. US, UK, EU">
                </div>
                <div class="col-12">
                    <label class="form-label">Keywords (comma-separated)</label>
                    <input class="form-control" wire:model="keywordsCsv" placeholder="AI, compliance, pricing, ...">
                </div>
            </div>

            <div class="mt-3">
                <button class="btn btn-primary" wire:click="createMonitor" wire:loading.attr="disabled">Create & run</button>
                <button class="btn btn-outline-secondary" wire:click="loadLatest" wire:loading.attr="disabled">Load latest report</button>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Monitors</div></div>
                <div class="card-body">
                    @forelse($monitors as $m)
                        <div class="border rounded p-2 mb-2">
                            <div class="fw-semibold">{{ $m->title }}</div>
                            <div class="text-muted small">{{ $m->industry }}{{ $m->region ? ' · ' . $m->region : '' }} · {{ $m->cadence }}</div>
                            <div class="text-muted small">Last run: {{ $m->last_run_at?->toDateTimeString() ?? '—' }}</div>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary" wire:click="runMonitor({{ $m->id }})">Run now</button>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">No monitors yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Latest report</div></div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded mb-0" style="white-space: pre-wrap;">{{ json_encode($lastReportPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        </div>
    </div>
</div>

