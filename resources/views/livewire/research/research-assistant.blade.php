<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Tools</div>
            <h2 class="page-title mb-0">Research Assistant</h2>
            <div class="text-muted small">Perplexity for research, Claude/GPT-4 for synthesis/creative.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('research.technical') }}">Technical advisor</a>
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
                        <option value="research">Research report</option>
                        <option value="competitive">Competitive analysis</option>
                        <option value="market">Market analysis</option>
                        <option value="content">Content / SEO research</option>
                        <option value="qa">Client Q&A (tracked)</option>
                        <option value="project">Project research dossier</option>
                        <option value="creative">Brainstorming (creative)</option>
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">Region (optional)</label>
                    <input class="form-control" wire:model="region" placeholder="e.g. US, UK, EU, APAC">
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">Depth</label>
                    <select class="form-select" wire:model="depth">
                        <option value="quick">Quick</option>
                        <option value="standard">Standard</option>
                        <option value="deep">Deep</option>
                    </select>
                </div>
            </div>

            <div class="row g-2 mt-2">
                @if(in_array($mode, ['research','content','project','qa','creative']))
                    <div class="col-12">
                        <label class="form-label">Topic</label>
                        <input class="form-control" wire:model="topic" placeholder="What should we research?">
                    </div>
                @endif

                @if($mode === 'market')
                    <div class="col-12 col-md-6">
                        <label class="form-label">Industry</label>
                        <input class="form-control" wire:model="industry" placeholder="e.g. SaaS, Healthcare, Ecommerce">
                    </div>
                @endif

                @if($mode === 'competitive')
                    <div class="col-12 col-md-6">
                        <label class="form-label">Client</label>
                        <select class="form-select" wire:model="clientId">
                            <option value="">-- select --</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Competitors (comma-separated)</label>
                        <input class="form-control" wire:model="competitorsCsv" placeholder="Competitor A, Competitor B, ...">
                    </div>
                @endif

                @if($mode === 'content')
                    <div class="col-12">
                        <label class="form-label">Audience</label>
                        <input class="form-control" wire:model="audience" placeholder="Who is this for?">
                    </div>
                @endif

                @if($mode === 'qa')
                    <div class="col-12">
                        <label class="form-label">Question</label>
                        <textarea class="form-control" wire:model="question" rows="3" placeholder="Ask a business/strategy question..."></textarea>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Attach to Request ID (optional)</label>
                        <input class="form-control" wire:model="requestId" placeholder="e.g. 123">
                    </div>
                @endif

                @if($mode === 'project')
                    <div class="col-12 col-md-6">
                        <label class="form-label">Request ID</label>
                        <input class="form-control" wire:model="requestId" placeholder="e.g. 123">
                    </div>
                @endif

                @if($mode === 'creative')
                    <div class="col-12">
                        <label class="form-label">Creative brief JSON</label>
                        <textarea class="form-control font-monospace" wire:model="briefJson" rows="6"></textarea>
                        <div class="text-muted small mt-1">Tip: include brand, goal, audience, tone, channels, constraints.</div>
                    </div>
                @endif
            </div>

            <div class="mt-3">
                <button class="btn btn-primary" wire:click="run" wire:loading.attr="disabled">
                    Run
                </button>
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

