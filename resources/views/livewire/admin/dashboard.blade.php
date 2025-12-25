<div class="space-y-4">
    <div class="row row-cards">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Total Active Clients</div>
                    <div class="h1 mb-0">{{ $activeClients }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Open Requests</div>
                    <div class="h1 mb-0">{{ array_sum($openRequestsByStatus) }}</div>
                    <div class="mt-2 small text-muted">
                        @foreach($openRequestsByStatus as $status => $count)
                            <span class="me-2">{{ ucfirst(str_replace('_',' ', $status)) }}: <strong>{{ $count }}</strong></span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Outstanding Invoice Amount</div>
                    <div class="h1 mb-0">${{ number_format($outstandingInvoiceAmount, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Revenue (This Month / Last Month)</div>
                    <div class="h1 mb-0">${{ number_format($revenueThisMonth, 2) }}</div>
                    <div class="mt-1 text-muted small">Last month: ${{ number_format($revenueLastMonth, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Active Contracts</div>
                    <div class="h1 mb-0">{{ $activeContracts }}</div>
                </div>
            </div>
        </div>

        <!-- Quick actions -->
        <div class="col-12 col-xl-9">
            <div class="card">
                <div class="card-body d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <div>
                        <div class="fw-semibold">Quick actions</div>
                        <div class="text-muted small">Jump to common admin workflows.</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.clients') }}" class="btn btn-primary">Create Client</a>
                        <a href="{{ route('admin.invoices') }}" class="btn btn-outline-primary">Create Invoice</a>
                        <a href="{{ route('admin.requests') }}" class="btn btn-outline-primary">Assign Request</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Request status funnel</div>
                </div>
                <div class="card-body">
                    <canvas id="adminRequestStatusChart" height="160"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Revenue trend (last 6 months)</div>
                </div>
                <div class="card-body">
                    <canvas id="adminRevenueTrendChart" height="160"></canvas>
                </div>
            </div>
        </div>

        <!-- Overdue invoices -->
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Overdue invoices</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Client</th>
                                <th class="text-end">Amount</th>
                                <th>Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($overdueInvoices as $inv)
                                <tr>
                                    <td class="fw-semibold">{{ $inv['invoice_number'] }}</td>
                                    <td>{{ $inv['client'] }}</td>
                                    <td class="text-end">${{ number_format((float) $inv['amount'], 2) }}</td>
                                    <td>{{ $inv['due_date'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No overdue invoices.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top clients -->
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Top clients by revenue</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th class="text-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topClients as $c)
                                <tr>
                                    <td class="fw-semibold">{{ $c['company'] }}</td>
                                    <td class="text-end">${{ number_format((float) $c['total'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-muted">No revenue yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent activity -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Recent activity (all clients)</div>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($recentActivity as $a)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <div class="fw-semibold">{{ $a['description'] }}</div>
                                <div class="text-muted small">{{ $a['when'] }}</div>
                            </div>
                            <div class="text-muted small">
                                {{ $a['user'] }} @if($a['client']) · {{ $a['client'] }} @endif · {{ $a['log'] }}
                            </div>
                        </div>
                    @empty
                        <div class="list-group-item text-muted">No activity yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const status = @json($requestStatusChart);
        const revenue = @json($revenueTrendChart);

        function initAdminCharts() {
            if (!window.Chart) return;
            window.__adminCharts = window.__adminCharts || {};

            const sEl = document.getElementById('adminRequestStatusChart');
            const rEl = document.getElementById('adminRevenueTrendChart');
            if (!sEl || !rEl) return;

            for (const key of ['status', 'revenue']) {
                if (window.__adminCharts[key]) {
                    try { window.__adminCharts[key].destroy(); } catch (e) {}
                    window.__adminCharts[key] = null;
                }
            }

            window.__adminCharts.status = new Chart(sEl.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: status.labels || [],
                    datasets: [{
                        label: 'Requests',
                        data: status.values || [],
                        backgroundColor: 'rgba(32, 107, 196, 0.85)',
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });

            window.__adminCharts.revenue = new Chart(rEl.getContext('2d'), {
                type: 'line',
                data: {
                    labels: revenue.labels || [],
                    datasets: [{
                        label: 'Revenue',
                        data: revenue.values || [],
                        borderColor: 'rgba(34, 197, 94, 1)',
                        backgroundColor: 'rgba(34, 197, 94, 0.10)',
                        tension: 0.35,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom' } },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: (v) => '$' + Number(v).toFixed(0) } }
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', initAdminCharts);
        document.addEventListener('livewire:initialized', initAdminCharts);
    })();
</script>
@endpush

