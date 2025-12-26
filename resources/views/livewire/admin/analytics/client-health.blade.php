<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Client health</h2>
            <div class="text-muted small">0–100 score with churn probability and risk level.</div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a class="btn btn-outline-primary" href="{{ route('admin.analytics.ai-insights') }}">AI insights</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.analytics.predictive') }}">Predictive</a>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <label class="form-label">Search</label>
                    <input class="form-control" wire:model.debounce.400ms="search" placeholder="Company or email">
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title mb-0">Clients</div></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th class="text-end">Score</th>
                            <th class="text-end">Churn</th>
                            <th>Risk</th>
                            <th>Updated</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clients as $c)
                            @php($s = $snapshots->get($c->id))
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $c->company_name }}</div>
                                    <div class="text-muted small">{{ $c->email }}</div>
                                </td>
                                <td class="text-end">
                                    @if($s)
                                        <span class="badge bg-{{ $s->score < 45 ? 'danger' : ($s->score < 70 ? 'warning' : 'success') }}">{{ $s->score }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($s)
                                        {{ number_format((float)$s->churn_probability, 2) }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($s)
                                        <span class="badge bg-{{ $s->risk_level === 'high' ? 'danger' : ($s->risk_level === 'medium' ? 'warning' : 'success') }}">{{ $s->risk_level }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted small">{{ $s?->computed_at?->toDateTimeString() ?? '—' }}</span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary" wire:click="refreshClient({{ $c->id }})">Refresh</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $clients->links() }}
        </div>
    </div>
</div>

