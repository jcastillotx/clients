<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">AI Insights</h2>
            <div class="text-muted small">Forecasts, alerts, and executive summaries.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('admin.analytics.predictive') }}">Predictive</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.analytics.client-health') }}">Client health</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="card mb-3">
                <div class="card-header"><div class="card-title mb-0">Revenue forecast (next 6 months)</div></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th class="text-end">Predicted</th>
                                    <th class="text-end">CI80 low</th>
                                    <th class="text-end">CI80 high</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($revenueForecast as $r)
                                    <tr>
                                        <td>{{ $r['month'] }}</td>
                                        <td class="text-end">${{ number_format((float)$r['predicted'], 2) }}</td>
                                        <td class="text-end">${{ number_format((float)$r['ci80_low'], 2) }}</td>
                                        <td class="text-end">${{ number_format((float)$r['ci80_high'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted small">Includes a conservative pipeline boost from open invoices due in the window.</div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><div class="card-title mb-0">Latest narrative reports</div></div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="fw-semibold">Weekly trends</div>
                        <div class="text-muted small">{{ $latestWeekly?->created_at?->toDateTimeString() ?? '—' }}</div>
                        <div style="white-space: pre-wrap;">{{ $latestWeekly?->narrative ?? 'No report yet.' }}</div>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <div class="fw-semibold">Monthly forecast</div>
                        <div class="text-muted small">{{ $latestMonthly?->created_at?->toDateTimeString() ?? '—' }}</div>
                        <div style="white-space: pre-wrap;">{{ $latestMonthly?->narrative ?? 'No report yet.' }}</div>
                    </div>
                    <hr>
                    <div>
                        <div class="fw-semibold">Quarterly BI</div>
                        <div class="text-muted small">{{ $latestQuarterly?->created_at?->toDateTimeString() ?? '—' }}</div>
                        <div style="white-space: pre-wrap;">{{ $latestQuarterly?->narrative ?? 'No report yet.' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card mb-3">
                <div class="card-header"><div class="card-title mb-0">Open alerts</div></div>
                <div class="card-body">
                    @forelse($alerts as $a)
                        <div class="border rounded p-2 mb-2">
                            <div class="fw-semibold">{{ $a->title }}</div>
                            <div class="text-muted small">{{ $a->type }} · {{ $a->severity }} · {{ $a->created_at?->toDateTimeString() }}</div>
                            <div class="text-muted" style="white-space: pre-wrap;">{{ $a->message }}</div>
                        </div>
                    @empty
                        <div class="text-muted">No alerts.</div>
                    @endforelse
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><div class="card-title mb-0">At-risk clients</div></div>
                <div class="card-body">
                    @forelse($atRisk as $s)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                <div class="fw-semibold">{{ $s->client?->company_name ?? ('Client #' . $s->client_id) }}</div>
                                <div class="text-muted small">Risk: {{ $s->risk_level }} · Churn: {{ number_format((float)$s->churn_probability, 2) }}</div>
                            </div>
                            <div class="badge bg-{{ $s->score < 45 ? 'danger' : ($s->score < 70 ? 'warning' : 'success') }}">
                                {{ $s->score }}
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">No health snapshots yet (daily job will populate).</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

