<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Reports</div>
            <h2 class="page-title mb-0">Financial Reports</h2>
            <div class="text-muted small">Revenue, receivables, invoice aging, and payment methods.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.reports') }}">Back</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label">Date range</label>
                    <select class="form-select" wire:model.live="range">
                        <option value="last_12_months">Last 12 months</option>
                        <option value="ytd">Year-to-date</option>
                        <option value="this_year">This year</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <label class="form-label">From</label>
                    <input type="date" class="form-control" wire:model.live="from" @disabled($range !== 'custom')>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <label class="form-label">To</label>
                    <input type="date" class="form-control" wire:model.live="to" @disabled($range !== 'custom')>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Revenue</div>
                <div class="h3 mb-0">@money($kpis['revenue'] ?? 0)</div>
            </div></div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Refunds</div>
                <div class="h3 mb-0">@money($kpis['refunds'] ?? 0)</div>
            </div></div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Net revenue</div>
                <div class="h3 mb-0">@money($kpis['net_revenue'] ?? 0)</div>
            </div></div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Outstanding receivables</div>
                <div class="h3 mb-0">@money($kpis['outstanding'] ?? 0)</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header">
                    <div class="card-title mb-0">Revenue</div>
                    <div class="ms-auto d-flex gap-2">
                        <button class="btn btn-sm btn-outline-secondary" wire:click="export('revenue_month','csv')">CSV</button>
                        <button class="btn btn-sm btn-outline-secondary" wire:click="export('revenue_month','xlsx')">Excel</button>
                        <button class="btn btn-sm btn-outline-secondary" wire:click="export('revenue_month','pdf')">PDF</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-2 align-items-end mb-2">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Group by</label>
                            <select class="form-select" wire:model.live="revenueGroup">
                                <option value="month">Month</option>
                                <option value="quarter">Quarter</option>
                                <option value="year">Year</option>
                            </select>
                        </div>
                    </div>
                    <canvas id="revByMonthChart" height="90"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Payment methods</div></div>
                <div class="card-body">
                    <canvas id="paymentMethodChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Revenue by client tier</div></div>
                <div class="card-body"><canvas id="revByTierChart" height="140"></canvas></div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Revenue by service type</div></div>
                <div class="card-body"><canvas id="revByServiceChart" height="140"></canvas></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title mb-0">Invoice aging (overdue balances)</div>
            <div class="ms-auto d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('invoice_aging','csv')">CSV</button>
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('invoice_aging','xlsx')">Excel</button>
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('invoice_aging','pdf')">PDF</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                <tr>
                    <th>Client</th>
                    <th class="text-end">0-30</th>
                    <th class="text-end">31-60</th>
                    <th class="text-end">61-90</th>
                    <th class="text-end">90+</th>
                    <th class="text-end">Total</th>
                </tr>
                </thead>
                <tbody>
                @forelse($invoiceAging as $r)
                    <tr>
                        <td class="fw-semibold">{{ $r['client'] }}</td>
                        <td class="text-end text-muted">@money($r['0-30'] ?? 0)</td>
                        <td class="text-end text-muted">@money($r['31-60'] ?? 0)</td>
                        <td class="text-end text-muted">@money($r['61-90'] ?? 0)</td>
                        <td class="text-end text-muted">@money($r['90+'] ?? 0)</td>
                        <td class="text-end fw-semibold">@money($r['total'] ?? 0)</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No overdue balances found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer text-muted small">
            Profit &amp; Loss note: this summary uses revenue/refunds plus optional labor cost from request time entries (if an hourly rate is set).
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <div class="card-title mb-0">Profit &amp; Loss summary (approx.)</div>
            <div class="ms-auto d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('pnl','csv')">CSV</button>
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('pnl','xlsx')">Excel</button>
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('pnl','pdf')">PDF</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr><th>Metric</th><th class="text-end">Value</th></tr></thead>
                <tbody>
                @foreach(($profitAndLoss ?? []) as $r)
                    @php($v = $r['value'])
                    <tr>
                        <td class="fw-semibold">{{ $r['label'] }}</td>
                        <td class="text-end text-muted">
                            @if(is_null($v))
                                N/A
                            @elseif(is_numeric($v) && str_contains(strtolower($r['label']), 'rate'))
                                @money($v)
                            @elseif(is_numeric($v) && (str_contains(strtolower($r['label']), 'revenue') || str_contains(strtolower($r['label']), 'cost') || str_contains(strtolower($r['label']), 'profit') || str_contains(strtolower($r['label']), 'refund')))
                                @money($v)
                            @else
                                {{ $v }}
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer text-muted small">
            To enable labor-cost profit estimates, set the system setting <code>billing.hourly_rate</code>.
            For true Profit &amp; Loss (real expenses/costs), we’ll need an expenses/costs model + table (today this report can only compute revenue/refunds/receivables from existing schema, plus optional estimated labor cost).
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                function renderCharts(data) {
                    const rev = data.revenueByPeriod || [];
                    const tiers = data.revenueByTier || [];
                    const svc = data.revenueByServiceType || [];
                    const pm = data.paymentMethods || [];

                    const byMonthCtx = document.getElementById('revByMonthChart');
                    const pmCtx = document.getElementById('paymentMethodChart');
                    const tierCtx = document.getElementById('revByTierChart');
                    const svcCtx = document.getElementById('revByServiceChart');

                    if (!window.Chart) return;
                    window.__charts = window.__charts || {};
                    Object.values(window.__charts).forEach(c => { try { c.destroy(); } catch(e) {} });
                    window.__charts = {};

                    window.__charts.revByMonth = new Chart(byMonthCtx, {
                        type: 'line',
                        data: {
                            labels: rev.map(x => x.label),
                            datasets: [{ label: 'Revenue', data: rev.map(x => x.value), borderWidth: 2 }]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });

                    window.__charts.paymentMethods = new Chart(pmCtx, {
                        type: 'doughnut',
                        data: {
                            labels: pm.map(x => x.label),
                            datasets: [{ data: pm.map(x => x.value) }]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });

                    window.__charts.revByTier = new Chart(tierCtx, {
                        type: 'bar',
                        data: {
                            labels: tiers.map(x => x.label),
                            datasets: [{ label: 'Revenue', data: tiers.map(x => x.value) }]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });

                    window.__charts.revByService = new Chart(svcCtx, {
                        type: 'bar',
                        data: {
                            labels: svc.map(x => x.label),
                            datasets: [{ label: 'Revenue', data: svc.map(x => x.value) }]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                }

                document.addEventListener('livewire:init', () => {
                    renderCharts({
                        revenueByPeriod: @json($revenueByPeriod),
                        revenueByTier: @json($revenueByTier),
                        revenueByServiceType: @json($revenueByServiceType),
                        paymentMethods: @json($paymentMethods),
                    });

                    Livewire.on('financial-report-updated', (payload) => {
                        renderCharts(payload || {});
                    });
                });
            })();
        </script>
    @endpush
</div>

