<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Reports</div>
            <h2 class="page-title mb-0">Request Reports</h2>
            <div class="text-muted small">Volume, completion times, staff output, SLA compliance.</div>
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
                <div class="text-muted small">Total requests</div>
                <div class="h3 mb-0">{{ $kpis['total'] ?? 0 }}</div>
            </div></div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Completed</div>
                <div class="h3 mb-0">{{ $kpis['completed'] ?? 0 }}</div>
            </div></div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Open</div>
                <div class="h3 mb-0">{{ $kpis['open'] ?? 0 }}</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-4">
            <div class="card"><div class="card-header"><div class="card-title mb-0">Volume by type</div></div>
                <div class="card-body"><canvas id="reqByTypeChart" height="180"></canvas></div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card"><div class="card-header"><div class="card-title mb-0">Volume by status</div></div>
                <div class="card-body"><canvas id="reqByStatusChart" height="180"></canvas></div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card"><div class="card-header"><div class="card-title mb-0">Volume by priority</div></div>
                <div class="card-body"><canvas id="reqByPriorityChart" height="180"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Average completion time (hours) by type</div></div>
                <div class="card-body"><canvas id="avgCompletionChart" height="160"></canvas></div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <div class="card-title mb-0">Staff productivity (completed)</div>
                    <div class="ms-auto d-flex gap-2">
                        <button class="btn btn-sm btn-outline-secondary" wire:click="export('staff_productivity','csv')">CSV</button>
                        <button class="btn btn-sm btn-outline-secondary" wire:click="export('staff_productivity','xlsx')">Excel</button>
                        <button class="btn btn-sm btn-outline-secondary" wire:click="export('staff_productivity','pdf')">PDF</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Staff</th><th class="text-end">Completed</th></tr></thead>
                        <tbody>
                        @forelse($staffProductivity as $r)
                            <tr>
                                <td class="fw-semibold">{{ $r['staff'] }}</td>
                                <td class="text-end text-muted">{{ $r['completed'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-4">No completed requests.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title mb-0">SLA compliance</div>
            <div class="ms-auto d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('sla','csv')">CSV</button>
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('sla','xlsx')">Excel</button>
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('sla','pdf')">PDF</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr><th>Metric</th><th class="text-end">Value</th></tr></thead>
                <tbody>
                @foreach($sla as $r)
                    <tr>
                        <td class="fw-semibold">{{ $r['metric'] }}</td>
                        <td class="text-end text-muted">{{ $r['value'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                function render(data) {
                    if (!window.Chart) return;
                    window.__reqCharts = window.__reqCharts || {};
                    Object.values(window.__reqCharts).forEach(c => { try { c.destroy(); } catch(e) {} });
                    window.__reqCharts = {};

                    const byType = data.volumeByType || [];
                    const byStatus = data.volumeByStatus || [];
                    const byPriority = data.volumeByPriority || [];
                    const avg = data.avgCompletionByType || [];

                    window.__reqCharts.byType = new Chart(document.getElementById('reqByTypeChart'), {
                        type: 'doughnut',
                        data: { labels: byType.map(x => x.label), datasets: [{ data: byType.map(x => x.value) }] },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                    window.__reqCharts.byStatus = new Chart(document.getElementById('reqByStatusChart'), {
                        type: 'doughnut',
                        data: { labels: byStatus.map(x => x.label), datasets: [{ data: byStatus.map(x => x.value) }] },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                    window.__reqCharts.byPriority = new Chart(document.getElementById('reqByPriorityChart'), {
                        type: 'doughnut',
                        data: { labels: byPriority.map(x => x.label), datasets: [{ data: byPriority.map(x => x.value) }] },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                    window.__reqCharts.avg = new Chart(document.getElementById('avgCompletionChart'), {
                        type: 'bar',
                        data: { labels: avg.map(x => x.label), datasets: [{ label: 'Hours', data: avg.map(x => x.value) }] },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                }

                document.addEventListener('livewire:init', () => {
                    render({
                        volumeByType: @json($volumeByType),
                        volumeByStatus: @json($volumeByStatus),
                        volumeByPriority: @json($volumeByPriority),
                        avgCompletionByType: @json($avgCompletionByType),
                    });
                    Livewire.on('request-report-updated', (payload) => {
                        render(payload || {});
                    });
                });
            })();
        </script>
    @endpush
</div>

