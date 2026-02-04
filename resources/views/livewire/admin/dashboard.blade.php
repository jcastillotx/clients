<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div>
            <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                <div class="p-6">
                    <div class="text-sm font-medium text-slate-500 mb-2">Total Active Clients</div>
                    <div class="text-3xl font-bold text-slate-900">{{ $activeClients }}</div>
                </div>
            </div>
        </div>
        <div>
            <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                <div class="p-6">
                    <div class="text-sm font-medium text-slate-500 mb-2">Open Requests</div>
                    <div class="text-3xl font-bold text-slate-900">{{ array_sum($openRequestsByStatus) }}</div>
                    <div class="mt-2 text-sm text-slate-500">
                        @foreach($openRequestsByStatus as $status => $count)
                            <span class="mr-2">{{ ucfirst(str_replace('_', ' ', $status)) }}:
                                <strong>{{ $count }}</strong></span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                <div class="p-6">
                    <div class="text-sm font-medium text-slate-500 mb-2">Outstanding Invoice Amount</div>
                    <div class="text-3xl font-bold text-slate-900">${{ number_format($outstandingInvoiceAmount, 2) }}
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                <div class="p-6">
                    <div class="text-sm font-medium text-slate-500 mb-2">Revenue (This Month / Last Month)</div>
                    <div class="text-3xl font-bold text-slate-900">${{ number_format($revenueThisMonth, 2) }}</div>
                    <div class="mt-1 text-sm text-slate-500">Last month: ${{ number_format($revenueLastMonth, 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-4">
        <div>
            <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                <div class="p-6">
                    <div class="text-sm font-medium text-slate-500 mb-2">Active Contracts</div>
                    <div class="text-3xl font-bold text-slate-900">{{ $activeContracts }}</div>
                </div>
            </div>
        </div>

        <!-- Quick actions -->
        <div class="xl:col-span-3">
            <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                <div class="p-6">
                    <div class="mb-4">
                        <div class="font-semibold text-slate-900 text-lg">Quick actions</div>
                        <div class="text-slate-500 text-sm">Jump to common admin workflows.</div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('admin.clients.create') }}"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                            <i class="fas fa-plus"></i>
                            Create Client
                        </a>
                        <a href="{{ route('admin.invoices.create') }}"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                            <i class="fas fa-file-invoice"></i>
                            Create Invoice
                        </a>
                        <a href="{{ route('admin.requests.index') }}"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                            <i class="fas fa-tasks"></i>
                            Assign Request
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div>
            <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <div class="font-semibold text-slate-900">Request status funnel</div>
                </div>
                <div class="p-6">
                    <canvas id="adminRequestStatusChart" height="160"></canvas>
                </div>
            </div>
        </div>
        <div>
            <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <div class="font-semibold text-slate-900">Revenue trend (last 6 months)</div>
                </div>
                <div class="p-6">
                    <canvas id="adminRevenueTrendChart" height="160"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue invoices and Top clients -->
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <div>
            <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <div class="font-semibold text-slate-900">Overdue invoices</div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                    Invoice</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                    Client</th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                    Amount</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                    Due</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse($overdueInvoices as $inv)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-3 text-sm font-semibold">{{ $inv['invoice_number'] }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $inv['client'] }}</td>
                                    <td class="px-4 py-3 text-sm text-right">${{ number_format((float) $inv['amount'], 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">{{ $inv['due_date'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-slate-500">No overdue invoices.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div>
            <div class="bg-white rounded-lg shadow-sm border border-slate-200">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <div class="font-semibold text-slate-900">Top clients by revenue</div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                    Client</th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                    Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse($topClients as $c)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-3 text-sm font-semibold">{{ $c['company'] }}</td>
                                    <td class="px-4 py-3 text-sm text-right">${{ number_format((float) $c['total'], 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-8 text-center text-slate-500">No revenue yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent activity -->
    <div class="bg-white rounded-lg shadow-sm border border-slate-200">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
            <div class="font-semibold text-slate-900">Recent activity (all clients)</div>
        </div>
        <div class="divide-y divide-slate-200">
            @forelse($recentActivity as $a)
                <div class="p-4 hover:bg-slate-50 transition-colors">
                    <div class="flex justify-between">
                        <div class="font-semibold text-slate-900">{{ $a['description'] }}</div>
                        <div class="text-slate-500 text-sm">{{ $a['when'] }}</div>
                    </div>
                    <div class="text-slate-500 text-sm">
                        {{ $a['user'] }} @if($a['client']) · {{ $a['client'] }} @endif · {{ $a['log'] }}
                    </div>
                </div>
            @empty
                <div class="p-4 text-slate-500">No activity yet.</div>
            @endforelse
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
                        try { window.__adminCharts[key].destroy(); } catch (e) { }
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