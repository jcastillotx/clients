<div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="page-pretitle">Admin</div>
            <h2 class="page-title mb-0">Predictive</h2>
            <div class="text-muted small">Revenue + request volume forecasting and capacity signals.</div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a class="btn btn-outline-primary" href="{{ route('admin.analytics.ai-insights') }}">AI insights</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.analytics.client-health') }}">Client health</a>
            <div class="ms-2">
                <label class="text-muted small mb-0">Months</label>
                <select class="form-select form-select-sm" wire:model="months" style="width: 110px;">
                    <option value="3">3</option>
                    <option value="6">6</option>
                    <option value="12">12</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="card mb-3">
                <div class="card-header"><div class="card-title mb-0">Revenue forecast</div></div>
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
                                @foreach(($rev['forecast'] ?? []) as $r)
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
                    <div class="text-muted small">
                        Pipeline within window: ${{ number_format((float)($rev['pipeline']['open_invoices_due_within_window'] ?? 0), 2) }} (invoices)
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><div class="card-title mb-0">Request volume forecast</div></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th class="text-end">Predicted requests</th>
                                    <th class="text-end">CI80 low</th>
                                    <th class="text-end">CI80 high</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(($vol['forecast'] ?? []) as $r)
                                    <tr>
                                        <td>{{ $r['month'] }}</td>
                                        <td class="text-end">{{ number_format((float)$r['predicted'], 0) }}</td>
                                        <td class="text-end">{{ number_format((float)$r['ci80_low'], 0) }}</td>
                                        <td class="text-end">{{ number_format((float)$r['ci80_high'], 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted small">Seasonality is learned from the last {{ $vol['history_months'] ?? 24 }} months.</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card mb-3">
                <div class="card-header"><div class="card-title mb-0">Resource allocation</div></div>
                <div class="card-body">
                    <div class="mb-2 text-muted small">
                        Unassigned open requests: <span class="fw-semibold">{{ $resources['unassigned_open_requests'] ?? 0 }}</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Staff</th>
                                    <th class="text-end">Open</th>
                                    <th class="text-end">Est. hours</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(($resources['staff_workload'] ?? []) as $row)
                                    <tr>
                                        <td>{{ $row['name'] }}</td>
                                        <td class="text-end">{{ $row['open_requests'] }}</td>
                                        <td class="text-end">{{ number_format((float)$row['estimated_hours'], 1) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(!empty($resources['suggestions']))
                        <div class="mt-3">
                            <div class="fw-semibold">Suggestions</div>
                            <ul class="mb-0">
                                @foreach($resources['suggestions'] as $s)
                                    <li>{{ $s }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

