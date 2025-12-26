<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">AI Usage</h2>
            <div class="text-muted small">Costs and token usage from `ai_usage_tracking` + most expensive tasks.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.providers') }}">Providers</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.tasks') }}">Task config</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.ai.audit') }}">Audit log</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">This month</div>
                    <div class="h3 mb-0">${{ number_format($monthSpend, 2) }}</div>
                    @if($budget > 0 && $budgetPct !== null)
                        <div class="text-muted small mt-1">
                            Budget: ${{ number_format($budget, 2) }} · {{ number_format($budgetPct * 100, 0) }}%
                        </div>
                        <div class="progress mt-2" style="height: 8px;">
                            <div class="progress-bar bg-{{ $budgetPct >= 1 ? 'danger' : ($budgetPct >= $alertPct ? 'warning' : 'success') }}"
                                 role="progressbar" style="width: {{ min(100, $budgetPct*100) }}%"></div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">This year</div>
                    <div class="h3 mb-0">${{ number_format($yearSpend, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small mb-2">Last 30 days (cost/day)</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Date</th><th class="text-end">Cost</th></tr></thead>
                            <tbody>
                                @foreach($trend as $t)
                                    <tr>
                                        <td>{{ $t['date'] }}</td>
                                        <td class="text-end">${{ number_format((float)$t['cost'], 2) }}</td>
                                    </tr>
                                @endforeach
                                @if(empty($trend))
                                    <tr><td colspan="2" class="text-muted">No usage yet.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card mb-3">
                <div class="card-header"><div class="card-title mb-0">Breakdown by provider (this month)</div></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Provider</th>
                                    <th class="text-end">Cost</th>
                                    <th class="text-end">Tokens in</th>
                                    <th class="text-end">Tokens out</th>
                                    <th class="text-end">Avg ms</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($byProvider as $r)
                                    <tr>
                                        <td class="text-uppercase">{{ $r['provider'] }}</td>
                                        <td class="text-end">${{ number_format($r['cost'], 2) }}</td>
                                        <td class="text-end">{{ number_format($r['tokens_input']) }}</td>
                                        <td class="text-end">{{ number_format($r['tokens_output']) }}</td>
                                        <td class="text-end">{{ $r['avg_ms'] !== null ? number_format($r['avg_ms']) : '—' }}</td>
                                    </tr>
                                @endforeach
                                @if($byProvider->isEmpty())
                                    <tr><td colspan="5" class="text-muted p-3">No usage this month.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Breakdown by task type (this month)</div></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Task</th><th class="text-end">Cost</th></tr></thead>
                            <tbody>
                                @foreach($byTask as $r)
                                    <tr>
                                        <td>{{ $r['task_type'] }}</td>
                                        <td class="text-end">${{ number_format($r['cost'], 2) }}</td>
                                    </tr>
                                @endforeach
                                @if($byTask->isEmpty())
                                    <tr><td colspan="2" class="text-muted p-3">No usage this month.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card mb-3">
                <div class="card-header"><div class="card-title mb-0">Top clients by cost (this month)</div></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Client</th><th class="text-end">Cost</th></tr></thead>
                            <tbody>
                                @foreach($byClient as $r)
                                    <tr>
                                        <td>{{ $r['client'] }}</td>
                                        <td class="text-end">${{ number_format($r['cost'], 2) }}</td>
                                    </tr>
                                @endforeach
                                @if($byClient->isEmpty())
                                    <tr><td colspan="2" class="text-muted p-3">No client usage this month.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><div class="card-title mb-0">Top users by cost (this month)</div></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>User</th><th class="text-end">Cost</th></tr></thead>
                            <tbody>
                                @foreach($byUser as $r)
                                    <tr>
                                        <td>{{ $r['user'] }}</td>
                                        <td class="text-end">${{ number_format($r['cost'], 2) }}</td>
                                    </tr>
                                @endforeach
                                @if($byUser->isEmpty())
                                    <tr><td colspan="2" class="text-muted p-3">No user usage this month.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Most expensive tasks (all-time)</div></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Task</th>
                                    <th>Provider</th>
                                    <th class="text-end">Cost</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mostExpensive as $t)
                                    <tr>
                                        <td>#{{ $t->id }}</td>
                                        <td>{{ $t->task_type }}</td>
                                        <td class="text-uppercase">{{ $t->provider_used ?: '—' }}</td>
                                        <td class="text-end">${{ number_format((float)$t->cost, 4) }}</td>
                                        <td><span class="badge bg-{{ $t->status === 'completed' ? 'success' : ($t->status === 'failed' ? 'danger' : 'secondary') }}">{{ $t->status }}</span></td>
                                    </tr>
                                @endforeach
                                @if($mostExpensive->isEmpty())
                                    <tr><td colspan="5" class="text-muted p-3">No tasks logged yet.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

