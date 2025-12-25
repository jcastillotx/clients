<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Reports</div>
            <h2 class="page-title mb-0">Performance Reports</h2>
            <div class="text-muted small">Response/resolution time trends and staff workload distribution.</div>
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
                <div class="text-muted small">Avg response time (hrs)</div>
                <div class="h3 mb-0">{{ number_format($kpis['avg_response_hours'] ?? 0, 1) }}</div>
                <div class="text-muted small">Approx: created → started</div>
            </div></div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Avg resolution time (hrs)</div>
                <div class="h3 mb-0">{{ number_format($kpis['avg_resolution_hours'] ?? 0, 1) }}</div>
                <div class="text-muted small">created → completed</div>
            </div></div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Client satisfaction</div>
                <div class="h3 mb-0">{{ $kpis['satisfaction'] === null ? 'N/A' : $kpis['satisfaction'] }}</div>
                <div class="text-muted small">Not tracked yet</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Avg response time by month (hrs)</div></div>
                <div class="card-body"><canvas id="respChart" height="160"></canvas></div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Avg resolution time by month (hrs)</div></div>
                <div class="card-body"><canvas id="resChart" height="160"></canvas></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title mb-0">Staff workload (open requests)</div>
            <div class="ms-auto d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('staff_workload','csv')">CSV</button>
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('staff_workload','xlsx')">Excel</button>
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('staff_workload','pdf')">PDF</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr><th>Staff</th><th class="text-end">Open requests</th></tr></thead>
                <tbody>
                @forelse($staffWorkload as $r)
                    <tr>
                        <td class="fw-semibold">{{ $r['staff'] }}</td>
                        <td class="text-end text-muted">{{ $r['open_requests'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-center text-muted py-4">No assigned open requests.</td></tr>
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
                    window.__perfCharts = window.__perfCharts || {};
                    Object.values(window.__perfCharts).forEach(c => { try { c.destroy(); } catch(e) {} });
                    window.__perfCharts = {};

                    const resp = data.avgResponseByMonth || [];
                    const res = data.avgResolutionByMonth || [];

                    window.__perfCharts.resp = new Chart(document.getElementById('respChart'), {
                        type: 'line',
                        data: { labels: resp.map(x => x.label), datasets: [{ label: 'Hours', data: resp.map(x => x.value), borderWidth: 2 }] },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                    window.__perfCharts.res = new Chart(document.getElementById('resChart'), {
                        type: 'line',
                        data: { labels: res.map(x => x.label), datasets: [{ label: 'Hours', data: res.map(x => x.value), borderWidth: 2 }] },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                }

                document.addEventListener('livewire:init', () => {
                    render({
                        avgResponseByMonth: @json($avgResponseByMonth),
                        avgResolutionByMonth: @json($avgResolutionByMonth),
                    });
                    Livewire.on('performance-report-updated', (payload) => render(payload || {}));
                });
            })();
        </script>
    @endpush
</div>

