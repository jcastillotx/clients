<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Reports</div>
            <h2 class="page-title mb-0">Client Reports</h2>
            <div class="text-muted small">Acquisition, tiers/status, top clients, churn risk.</div>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.reports') }}">Back</a>
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
        <div class="col-12 col-lg-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Total clients</div>
                <div class="h3 mb-0">{{ $kpis['total_clients'] ?? 0 }}</div>
            </div></div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">New clients (range)</div>
                <div class="h3 mb-0">{{ $kpis['new_clients'] ?? 0 }}</div>
            </div></div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Retention (approx.)</div>
                <div class="h3 mb-0">{{ number_format(($kpis['retention_rate'] ?? 0) * 100, 1) }}%</div>
            </div></div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Avg lifetime value (all time)</div>
                <div class="h3 mb-0">@money($kpis['avg_ltv'] ?? 0)</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">New clients by month</div></div>
                <div class="card-body"><canvas id="newClientsChart" height="140"></canvas></div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Clients by tier</div></div>
                <div class="card-body"><canvas id="clientsByTierChart" height="140"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Clients by status</div></div>
                <div class="card-body"><canvas id="clientsByStatusChart" height="140"></canvas></div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <div class="card-title mb-0">Top clients</div>
                    <div class="ms-auto d-flex gap-2">
                        <button class="btn btn-sm btn-outline-secondary" wire:click="export('top_clients','csv')">CSV</button>
                        <button class="btn btn-sm btn-outline-secondary" wire:click="export('top_clients','xlsx')">Excel</button>
                        <button class="btn btn-sm btn-outline-secondary" wire:click="export('top_clients','pdf')">PDF</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                        <tr>
                            <th>Client</th>
                            <th>Tier</th>
                            <th>Status</th>
                            <th class="text-end">Revenue</th>
                            <th class="text-end">Requests</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($topClients as $r)
                            <tr>
                                <td class="fw-semibold">{{ $r['client'] }}</td>
                                <td class="text-muted">{{ $r['tier'] }}</td>
                                <td class="text-muted">{{ $r['status'] }}</td>
                                <td class="text-end">@money($r['revenue'] ?? 0)</td>
                                <td class="text-end text-muted">{{ $r['requests'] ?? 0 }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No data.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title mb-0">Churn risk (inactive ≥ 60 days)</div>
            <div class="ms-auto d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('churn_risk','csv')">CSV</button>
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('churn_risk','xlsx')">Excel</button>
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('churn_risk','pdf')">PDF</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                <tr>
                    <th>Client</th>
                    <th>Tier</th>
                    <th>Status</th>
                    <th>Last activity</th>
                </tr>
                </thead>
                <tbody>
                @forelse($churnRisk as $r)
                    <tr>
                        <td class="fw-semibold">{{ $r['client'] }}</td>
                        <td class="text-muted">{{ $r['tier'] }}</td>
                        <td class="text-muted">{{ $r['status'] }}</td>
                        <td class="text-muted">{{ $r['last_activity'] ? \Carbon\Carbon::parse($r['last_activity'])->format('Y-m-d') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No churn-risk clients.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer text-muted small">
            Retention/churn are approximations based on the presence/absence of activity log entries.
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <div class="card-title mb-0">Client lifetime value (top 20, all time)</div>
            <div class="ms-auto d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('ltv','csv')">CSV</button>
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('ltv','xlsx')">Excel</button>
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('ltv','pdf')">PDF</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr><th>Client</th><th>Tier</th><th class="text-end">LTV</th></tr></thead>
                <tbody>
                @forelse($lifetimeValue ?? [] as $r)
                    <tr>
                        <td class="fw-semibold">{{ $r['client'] }}</td>
                        <td class="text-muted">{{ $r['tier'] }}</td>
                        <td class="text-end">@money($r['ltv'] ?? 0)</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">No payment data yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                function render(data) {
                    if (!window.Chart) return;
                    window.__clientCharts = window.__clientCharts || {};
                    Object.values(window.__clientCharts).forEach(c => { try { c.destroy(); } catch(e) {} });
                    window.__clientCharts = {};

                    const newClients = data.newClientsByMonth || [];
                    const tiers = data.clientsByTier || [];
                    const statuses = data.clientsByStatus || [];

                    window.__clientCharts.newClients = new Chart(document.getElementById('newClientsChart'), {
                        type: 'bar',
                        data: { labels: newClients.map(x => x.label), datasets: [{ label: 'New clients', data: newClients.map(x => x.value) }] },
                        options: { responsive: true, maintainAspectRatio: false }
                    });

                    window.__clientCharts.byTier = new Chart(document.getElementById('clientsByTierChart'), {
                        type: 'doughnut',
                        data: { labels: tiers.map(x => x.label), datasets: [{ data: tiers.map(x => x.value) }] },
                        options: { responsive: true, maintainAspectRatio: false }
                    });

                    window.__clientCharts.byStatus = new Chart(document.getElementById('clientsByStatusChart'), {
                        type: 'doughnut',
                        data: { labels: statuses.map(x => x.label), datasets: [{ data: statuses.map(x => x.value) }] },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                }

                document.addEventListener('livewire:init', () => {
                    render({
                        newClientsByMonth: @json($newClientsByMonth),
                        clientsByTier: @json($clientsByTier),
                        clientsByStatus: @json($clientsByStatus),
                    });

                    Livewire.on('client-report-updated', (payload) => {
                        render(payload || {});
                    });
                });
            })();
        </script>
    @endpush
</div>

