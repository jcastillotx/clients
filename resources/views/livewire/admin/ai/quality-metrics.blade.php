<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">AI Quality Metrics</h2>
            <div class="text-muted small">Ratings + performance signals over the last 30 days.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.safety') }}">Safety</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.review-queue') }}">Review queue</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Average rating</div>
                <div class="h3 mb-0">{{ number_format($avgRating, 2) }} / 5</div>
                <div class="text-muted small">Rated tasks: {{ $ratedCount }}</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card mb-3">
                <div class="card-header"><div class="card-title mb-0">Average rating by task type</div></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Task</th><th class="text-end">Avg</th><th class="text-end">Count</th></tr></thead>
                            <tbody>
                                @foreach($byTask as $r)
                                    <tr>
                                        <td>{{ $r->task_type }}</td>
                                        <td class="text-end">{{ number_format((float)$r->avg_rating, 2) }}</td>
                                        <td class="text-end">{{ $r->cnt }}</td>
                                    </tr>
                                @endforeach
                                @if($byTask->isEmpty())
                                    <tr><td colspan="3" class="text-muted p-3">No rated tasks in window.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Rating trend (30 days)</div></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Date</th><th class="text-end">Avg rating</th></tr></thead>
                            <tbody>
                                @foreach($trend as $t)
                                    <tr>
                                        <td>{{ $t->d }}</td>
                                        <td class="text-end">{{ number_format((float)$t->avg_rating, 2) }}</td>
                                    </tr>
                                @endforeach
                                @if($trend->isEmpty())
                                    <tr><td colspan="2" class="text-muted p-3">No ratings yet.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Provider performance (30 days)</div></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Provider</th><th class="text-end">Avg ms</th><th class="text-end">Cost</th></tr></thead>
                            <tbody>
                                @foreach($byProvider as $r)
                                    <tr>
                                        <td class="text-uppercase">{{ $r->provider }}</td>
                                        <td class="text-end">{{ $r->avg_ms !== null ? number_format((float)$r->avg_ms, 0) : '—' }}</td>
                                        <td class="text-end">${{ number_format((float)$r->cost, 2) }}</td>
                                    </tr>
                                @endforeach
                                @if($byProvider->isEmpty())
                                    <tr><td colspan="3" class="text-muted p-3">No usage tracked.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

