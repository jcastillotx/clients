<div>
    <h2 class="mb-3">Industry insights</h2>

    @if($canAdd)
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus mr-1"></i> Add insight</h3>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="col-md-3">
                        <label class="mb-1">Industry (optional)</label>
                        <input class="form-control" wire:model="industry" placeholder="e.g. Dental, SaaS">
                    </div>
                    <div class="col-md-3">
                        <label class="mb-1">Type</label>
                        <select class="form-control" wire:model="insightType">
                            <option value="news">news</option>
                            <option value="trend">trend</option>
                            <option value="report">report</option>
                            <option value="alert">alert</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="mb-1">Title</label>
                        <input class="form-control" wire:model="title">
                    </div>
                </div>
                <div class="form-group mt-2">
                    <label class="mb-1">Content</label>
                    <textarea class="form-control" rows="3" wire:model="content"></textarea>
                </div>
                <div class="form-group">
                    <label class="mb-1">Source URL</label>
                    <input class="form-control" wire:model="sourceUrl" placeholder="https://...">
                </div>
                <button class="btn btn-primary" wire:click="add"><i class="fas fa-save mr-1"></i> Add</button>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-rss mr-1"></i> Feed</h3>
        </div>
        <div class="card-body">
            @forelse($insights as $i)
                <div class="border rounded p-2 mb-2">
                    <div class="d-flex justify-content-between">
                        <div class="font-weight-bold">{{ $i->title }}</div>
                        <div class="text-muted small">{{ $i->published_at?->toDateString() }}</div>
                    </div>
                    <div class="text-muted small">{{ $i->industry ? $i->industry . ' · ' : '' }}{{ $i->insight_type }}</div>
                    @if($i->content)
                        <div class="mt-2" style="white-space: pre-wrap;">{{ $i->content }}</div>
                    @endif
                    @if($i->source_url)
                        <div class="mt-2"><a href="{{ $i->source_url }}" target="_blank" rel="noopener">Source</a></div>
                    @endif
                </div>
            @empty
                <div class="text-muted">No insights yet.</div>
            @endforelse
        </div>
    </div>
</div>

