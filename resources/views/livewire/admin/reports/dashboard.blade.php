<x-app-layout>
    <x-slot name="header">Admin Reporting</x-slot>

    <div class="card">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label class="mb-1">Category</label>
                    <select class="form-control" wire:model.live="category">
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="mb-1">Start date</label>
                    <input type="date" class="form-control" wire:model.defer="start_date">
                </div>

                <div class="col-md-2">
                    <label class="mb-1">End date</label>
                    <input type="date" class="form-control" wire:model.defer="end_date">
                </div>

                <div class="col-md-2">
                    <label class="mb-1">Granularity</label>
                    <select class="form-control" wire:model.live="granularity" @disabled($category !== 'financial')>
                        <option value="month">Month</option>
                        <option value="quarter">Quarter</option>
                        <option value="year">Year</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <button class="btn btn-primary btn-block" wire:click="apply">
                        <i class="fas fa-sync-alt mr-1"></i> Apply Filters
                    </button>
                </div>
            </div>

            <hr>

            <div class="d-flex flex-wrap" style="gap: 8px;">
                <a class="btn btn-outline-secondary btn-sm"
                   href="{{ route('admin.reports.export', ['category' => $category, 'format' => 'csv'] + $this->exportQuery) }}">
                    <i class="fas fa-file-csv mr-1"></i> Export CSV
                </a>
                <a class="btn btn-outline-secondary btn-sm"
                   href="{{ route('admin.reports.export', ['category' => $category, 'format' => 'xlsx'] + $this->exportQuery) }}">
                    <i class="fas fa-file-excel mr-1"></i> Export Excel
                </a>
                <a class="btn btn-outline-secondary btn-sm"
                   href="{{ route('admin.reports.export', ['category' => $category, 'format' => 'pdf'] + $this->exportQuery) }}">
                    <i class="fas fa-file-pdf mr-1"></i> Export PDF
                </a>

                <div class="ml-auto text-muted">
                    @if(!empty($payload['meta']['start']) && !empty($payload['meta']['end']))
                        Range: {{ $payload['meta']['start'] }} → {{ $payload['meta']['end'] }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($category === 'financial')
        @include('livewire.admin.reports.financial')
    @elseif($category === 'clients')
        @include('livewire.admin.reports.clients')
    @elseif($category === 'requests')
        @include('livewire.admin.reports.requests')
    @else
        <div class="card">
            <div class="card-body">
                <div class="alert alert-info mb-0">
                    This category is available for export and tables, but the main dashboard charts are currently implemented for Financial, Clients, and Requests.
                </div>

                @include('livewire.admin.reports._tables', ['tables' => $payload['tables'] ?? []])
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-sliders-h mr-1"></i>
                Custom Report Builder (templates + scheduling)
            </h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-2">Save template</h5>
                    <div class="form-group">
                        <label class="mb-1">Template name</label>
                        <input class="form-control" wire:model.defer="template_name" placeholder="e.g. Monthly Revenue + Aging">
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Description (optional)</label>
                        <textarea class="form-control" rows="2" wire:model.defer="template_description"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Metrics</label>
                        <div class="row">
                            @forelse($metricOptions as $metricKey => $metricLabel)
                                <div class="col-12 col-lg-6">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               id="metric_{{ $metricKey }}"
                                               value="{{ $metricKey }}"
                                               wire:model.defer="template_metrics">
                                        <label class="custom-control-label" for="metric_{{ $metricKey }}">{{ $metricLabel }}</label>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-muted">(No metric options for this category)</div>
                            @endforelse
                        </div>
                    </div>
                    <button class="btn btn-success" wire:click="saveTemplate">
                        <i class="fas fa-save mr-1"></i> Save Template
                    </button>
                </div>

                <div class="col-md-6">
                    <h5 class="mb-2">Schedule email delivery</h5>
                    <div class="form-group">
                        <label class="mb-1">Template</label>
                        <select class="form-control" wire:model.defer="schedule_template_id">
                            <option value="">Select a template...</option>
                            @foreach($this->templates as $t)
                                <option value="{{ $t->id }}">#{{ $t->id }} — {{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Frequency</label>
                        <select class="form-control" wire:model.defer="schedule_frequency">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="mb-1">Recipients (comma separated)</label>
                        <input class="form-control" wire:model.defer="schedule_recipients" placeholder="admin@company.com, ops@company.com">
                    </div>
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="sched_active" wire:model.defer="schedule_is_active">
                        <label class="custom-control-label" for="sched_active">Active</label>
                    </div>
                    <button class="btn btn-primary" wire:click="createSchedule">
                        <i class="fas fa-paper-plane mr-1"></i> Create Schedule
                    </button>
                </div>
            </div>

            <hr>

            <h5 class="mb-2">Recent templates</h5>
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Granularity</th>
                            <th>Metrics</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->templates as $t)
                            <tr>
                                <td>{{ $t->id }}</td>
                                <td>{{ $t->name }}</td>
                                <td>{{ data_get($t->config, 'category') }}</td>
                                <td>{{ data_get($t->config, 'granularity') }}</td>
                                <td>{{ implode(', ', (array) data_get($t->config, 'metrics', [])) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No templates saved yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <h5 class="mb-2">Recent schedules</h5>
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Template</th>
                            <th>Frequency</th>
                            <th>Recipients</th>
                            <th>Active</th>
                            <th>Next run</th>
                            <th>Last run</th>
                            <th>Last error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->schedules as $s)
                            <tr>
                                <td>{{ $s->id }}</td>
                                <td>{{ $s->template?->name }}</td>
                                <td>{{ $s->frequency }}</td>
                                <td>{{ implode(', ', (array) $s->recipients) }}</td>
                                <td>{!! $s->is_active ? '<span class="badge badge-success">yes</span>' : '<span class="badge badge-secondary">no</span>' !!}</td>
                                <td>{{ optional($s->next_run_at)->toDateTimeString() }}</td>
                                <td>{{ optional($s->last_run_at)->toDateTimeString() }}</td>
                                <td class="text-danger">{{ $s->last_error }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-muted">No schedules created yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                let charts = {};

                function destroy(id) {
                    if (charts[id]) {
                        charts[id].destroy();
                        delete charts[id];
                    }
                }

                function money(v) {
                    if (v === null || v === undefined) return '';
                    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(v);
                }

                function renderFinancial(payload) {
                    const chartsData = (payload && payload.charts) ? payload.charts : {};

                    // Revenue trend
                    if (document.getElementById('revenueTrendChart')) {
                        destroy('revenueTrendChart');
                        const rows = chartsData.revenueTrend || [];
                        charts['revenueTrendChart'] = new Chart(document.getElementById('revenueTrendChart'), {
                            type: 'line',
                            data: {
                                labels: rows.map(r => r.period),
                                datasets: [{
                                    label: 'Revenue',
                                    data: rows.map(r => r.revenue),
                                    borderColor: '#3c8dbc',
                                    backgroundColor: 'rgba(60, 141, 188, 0.15)',
                                    fill: true,
                                    tension: 0.25,
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    tooltip: { callbacks: { label: (ctx) => money(ctx.parsed.y) } }
                                },
                                scales: { y: { ticks: { callback: (v) => money(v) } } }
                            }
                        });
                    }

                    // Revenue by tier
                    if (document.getElementById('revenueByTierChart')) {
                        destroy('revenueByTierChart');
                        const rows = chartsData.revenueByTier || [];
                        charts['revenueByTierChart'] = new Chart(document.getElementById('revenueByTierChart'), {
                            type: 'bar',
                            data: {
                                labels: rows.map(r => r.tier),
                                datasets: [{
                                    label: 'Revenue',
                                    data: rows.map(r => r.revenue),
                                    backgroundColor: '#00a65a',
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: { tooltip: { callbacks: { label: (ctx) => money(ctx.parsed.y) } } },
                                scales: { y: { ticks: { callback: (v) => money(v) } } }
                            }
                        });
                    }

                    // Payment methods
                    if (document.getElementById('paymentMethodsChart')) {
                        destroy('paymentMethodsChart');
                        const rows = chartsData.paymentMethods || [];
                        charts['paymentMethodsChart'] = new Chart(document.getElementById('paymentMethodsChart'), {
                            type: 'doughnut',
                            data: {
                                labels: rows.map(r => r.payment_method),
                                datasets: [{
                                    data: rows.map(r => r.total),
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: { tooltip: { callbacks: { label: (ctx) => money(ctx.parsed) } } }
                            }
                        });
                    }

                    // Invoice aging
                    if (document.getElementById('invoiceAgingChart')) {
                        destroy('invoiceAgingChart');
                        const rows = chartsData.invoiceAging || [];
                        charts['invoiceAgingChart'] = new Chart(document.getElementById('invoiceAgingChart'), {
                            type: 'bar',
                            data: {
                                labels: rows.map(r => r.bucket),
                                datasets: [{
                                    label: 'Amount',
                                    data: rows.map(r => r.amount),
                                    backgroundColor: '#f39c12',
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: { tooltip: { callbacks: { label: (ctx) => money(ctx.parsed.y) } } },
                                scales: { y: { ticks: { callback: (v) => money(v) } } }
                            }
                        });
                    }
                }

                function renderClients(payload) {
                    const chartsData = (payload && payload.charts) ? payload.charts : {};

                    if (document.getElementById('clientAcquisitionChart')) {
                        destroy('clientAcquisitionChart');
                        const rows = chartsData.acquisition || [];
                        charts['clientAcquisitionChart'] = new Chart(document.getElementById('clientAcquisitionChart'), {
                            type: 'line',
                            data: {
                                labels: rows.map(r => r.period),
                                datasets: [{
                                    label: 'New Clients',
                                    data: rows.map(r => r.new_clients),
                                    borderColor: '#605ca8',
                                    backgroundColor: 'rgba(96, 92, 168, 0.15)',
                                    fill: true,
                                    tension: 0.25,
                                }]
                            },
                            options: { responsive: true }
                        });
                    }

                    if (document.getElementById('clientsByTierChart')) {
                        destroy('clientsByTierChart');
                        const rows = chartsData.clientsByTier || [];
                        charts['clientsByTierChart'] = new Chart(document.getElementById('clientsByTierChart'), {
                            type: 'bar',
                            data: {
                                labels: rows.map(r => r.tier),
                                datasets: [{
                                    label: 'Clients',
                                    data: rows.map(r => r.count),
                                    backgroundColor: '#00c0ef',
                                }]
                            },
                            options: { responsive: true }
                        });
                    }

                    if (document.getElementById('clientsByStatusChart')) {
                        destroy('clientsByStatusChart');
                        const rows = chartsData.clientsByStatus || [];
                        charts['clientsByStatusChart'] = new Chart(document.getElementById('clientsByStatusChart'), {
                            type: 'doughnut',
                            data: {
                                labels: rows.map(r => r.status),
                                datasets: [{ data: rows.map(r => r.count) }]
                            },
                            options: { responsive: true }
                        });
                    }
                }

                function renderRequests(payload) {
                    const chartsData = (payload && payload.charts) ? payload.charts : {};

                    if (document.getElementById('requestsByTypeChart')) {
                        destroy('requestsByTypeChart');
                        const rows = chartsData.byType || [];
                        charts['requestsByTypeChart'] = new Chart(document.getElementById('requestsByTypeChart'), {
                            type: 'bar',
                            data: {
                                labels: rows.map(r => r.type),
                                datasets: [{
                                    label: 'Requests',
                                    data: rows.map(r => r.count),
                                    backgroundColor: '#3c8dbc',
                                }]
                            },
                            options: { responsive: true }
                        });
                    }

                    if (document.getElementById('requestsByStatusChart')) {
                        destroy('requestsByStatusChart');
                        const rows = chartsData.byStatus || [];
                        charts['requestsByStatusChart'] = new Chart(document.getElementById('requestsByStatusChart'), {
                            type: 'bar',
                            data: {
                                labels: rows.map(r => r.status),
                                datasets: [{
                                    label: 'Requests',
                                    data: rows.map(r => r.count),
                                    backgroundColor: '#00a65a',
                                }]
                            },
                            options: { responsive: true }
                        });
                    }

                    if (document.getElementById('requestsByPriorityChart')) {
                        destroy('requestsByPriorityChart');
                        const rows = chartsData.byPriority || [];
                        charts['requestsByPriorityChart'] = new Chart(document.getElementById('requestsByPriorityChart'), {
                            type: 'bar',
                            data: {
                                labels: rows.map(r => r.priority),
                                datasets: [{
                                    label: 'Requests',
                                    data: rows.map(r => r.count),
                                    backgroundColor: '#f39c12',
                                }]
                            },
                            options: { responsive: true }
                        });
                    }
                }

                function render(payload) {
                    const category = payload && payload.meta ? payload.meta.category : null;
                    if (category === 'financial') return renderFinancial(payload);
                    if (category === 'clients') return renderClients(payload);
                    if (category === 'requests') return renderRequests(payload);
                }

                window.addEventListener('reports-updated', function (e) {
                    render(e.detail.payload);
                });

                // Initial render
                render(@json($payload));
            })();
        </script>
    @endpush
</x-app-layout>

