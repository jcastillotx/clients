<div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="page-pretitle">Reports</div>
            <h2 class="page-title mb-0">Storage Reports</h2>
            <div class="text-muted small">Usage, file types, large file alerts, sync success rate.</div>
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
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Usage by client (top 20)</div></div>
                <div class="card-body"><canvas id="usageByClientChart" height="170"></canvas></div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Usage by provider</div></div>
                <div class="card-body"><canvas id="usageByProviderChart" height="170"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">File type distribution (top 10)</div></div>
                <div class="card-body"><canvas id="fileTypesChart" height="170"></canvas></div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header"><div class="card-title mb-0">Sync success rate</div></div>
                <div class="card-body">
                    <div class="h3 mb-1">{{ number_format(($sync['rate'] ?? 0) * 100, 1) }}%</div>
                    <div class="text-muted small">{{ $sync['success'] ?? 0 }} success / {{ $sync['total'] ?? 0 }} runs</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title mb-0">Large file alerts (top 20)</div>
            <div class="ms-auto d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('large_files','csv')">CSV</button>
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('large_files','xlsx')">Excel</button>
                <button class="btn btn-sm btn-outline-secondary" wire:click="export('large_files','pdf')">PDF</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                <tr>
                    <th>Client</th>
                    <th>Provider</th>
                    <th>File</th>
                    <th class="text-end">Size</th>
                    <th>Synced</th>
                </tr>
                </thead>
                <tbody>
                @forelse($largeFiles as $r)
                    <tr>
                        <td class="fw-semibold">{{ $r['client'] ?? '—' }}</td>
                        <td class="text-muted">{{ $r['provider'] ?? '—' }}</td>
                        <td class="text-muted">{{ $r['file_name'] ?? '—' }}</td>
                        <td class="text-end text-muted">{{ number_format($r['file_size'] ?? 0) }}</td>
                        <td class="text-muted">{{ $r['synced_at'] ? \Carbon\Carbon::parse($r['synced_at'])->format('Y-m-d') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No files found.</td></tr>
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
                    window.__storageCharts = window.__storageCharts || {};
                    Object.values(window.__storageCharts).forEach(c => { try { c.destroy(); } catch(e) {} });
                    window.__storageCharts = {};

                    const byClient = data.usageByClient || [];
                    const byProvider = data.usageByProvider || [];
                    const types = data.fileTypes || [];

                    window.__storageCharts.byClient = new Chart(document.getElementById('usageByClientChart'), {
                        type: 'bar',
                        data: { labels: byClient.map(x => x.label), datasets: [{ label: 'Bytes', data: byClient.map(x => x.value) }] },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                    window.__storageCharts.byProvider = new Chart(document.getElementById('usageByProviderChart'), {
                        type: 'doughnut',
                        data: { labels: byProvider.map(x => x.label), datasets: [{ data: byProvider.map(x => x.value) }] },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                    window.__storageCharts.types = new Chart(document.getElementById('fileTypesChart'), {
                        type: 'doughnut',
                        data: { labels: types.map(x => x.label), datasets: [{ data: types.map(x => x.value) }] },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                }

                document.addEventListener('livewire:init', () => {
                    render({
                        usageByClient: @json($usageByClient),
                        usageByProvider: @json($usageByProvider),
                        fileTypes: @json($fileTypes),
                    });
                    Livewire.on('storage-report-updated', (payload) => render(payload || {}));
                });
            })();
        </script>
    @endpush
</div>

