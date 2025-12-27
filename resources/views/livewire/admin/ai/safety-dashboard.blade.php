<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">AI Safety Dashboard</h2>
            <div class="text-muted small">Compliance signals, PII detections, and review queue health (last 7 days).</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.review-queue') }}">Review queue</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.quality') }}">Quality</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.usage') }}">Usage</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Pending reviews</div>
                <div class="h3 mb-0">{{ $pendingReviews }}</div>
            </div></div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">PII detections (7d)</div>
                <div class="h3 mb-0">{{ $pii7 }}</div>
            </div></div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Flagged for review (7d)</div>
                <div class="h3 mb-0">{{ $flagged7 }}</div>
            </div></div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Spend this month</div>
                <div class="h3 mb-0">${{ number_format($monthSpend, 2) }}</div>
                @if($budget > 0)
                    <div class="text-muted small">Budget: ${{ number_format($budget, 2) }}</div>
                @endif
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title mb-0">Top flags (7 days)</div></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Flags JSON</th><th class="text-end">Count</th></tr></thead>
                    <tbody>
                        @foreach($byFlag as $r)
                            <tr>
                                <td class="text-muted small" style="white-space: pre-wrap;">{{ json_encode($r->flags, JSON_UNESCAPED_SLASHES) }}</td>
                                <td class="text-end">{{ $r->cnt }}</td>
                            </tr>
                        @endforeach
                        @if($byFlag->isEmpty())
                            <tr><td colspan="2" class="text-muted p-3">No compliance logs yet.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

