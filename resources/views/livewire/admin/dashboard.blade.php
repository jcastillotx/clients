<div class="space-y-8">
    {{-- Header KPI Cards - Modernized --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
        {{-- Card: Total Active Clients --}}
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-start justify-between">
                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-brand-primary/10 transition-transform duration-300 group-hover:scale-110">
                    <x-icon name="users" class="h-7 w-7 text-brand-primary" />
                </div>
                @php $clientGrowth = 2.5; @endphp
                <div class="flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-600 dark:bg-emerald-900/20">
                    <x-icon name="chevron-up" class="h-4 w-4" />
                    <span>{{ abs($clientGrowth) }}%</span>
                </div>
            </div>
            <div class="mt-6">
                <h3 class="text-3xl font-bold tracking-tight text-brand-text dark:text-white">{{ $activeClients }}</h3>
                <p class="mt-1 text-sm font-medium text-brand-muted dark:text-slate-400">Total Active Clients</p>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-brand-primary to-brand-secondary opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
        </div>

        {{-- Card: Open Requests --}}
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-start justify-between">
                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-indigo-50 transition-transform duration-300 group-hover:scale-110">
                    <x-icon name="clipboard-list" class="h-7 w-7 text-indigo-600" />
                </div>
                <div class="flex items-center justify-center rounded-full bg-indigo-50 px-3 py-1 text-sm font-bold text-indigo-600">
                    {{ array_sum($openRequestsByStatus) }}
                </div>
            </div>
            <div class="mt-6">
                <h3 class="text-3xl font-bold tracking-tight text-brand-text dark:text-white">{{ array_sum($openRequestsByStatus) }}</h3>
                <div class="mt-1 flex items-center justify-between">
                    <p class="text-sm font-medium text-brand-muted dark:text-slate-400">Open Requests</p>
                    <span class="text-xs font-medium text-indigo-600">Pending</span>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-indigo-500 to-indigo-300 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
        </div>

        {{-- Card: Revenue --}}
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-start justify-between">
                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-emerald-50 transition-transform duration-300 group-hover:scale-110">
                    <x-icon name="currency-dollar" class="h-7 w-7 text-emerald-600" />
                </div>
                @php
                    $revChange = $revenueLastMonth > 0 ? (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100 : 100;
                @endphp
                <div class="flex items-center gap-1 rounded-full {{ $revChange >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' }} px-3 py-1 text-sm font-semibold">
                    <x-icon name="chevron-{{ $revChange >= 0 ? 'up' : 'down' }}" class="h-4 w-4" />
                    <span>{{ number_format(abs($revChange), 1) }}%</span>
                </div>
            </div>
            <div class="mt-6">
                <h3 class="text-3xl font-bold tracking-tight text-brand-text dark:text-white">${{ number_format($revenueThisMonth, 2) }}</h3>
                <p class="mt-1 text-sm font-medium text-brand-muted dark:text-slate-400">Revenue This Month</p>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-emerald-500 to-emerald-300 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
        </div>

        {{-- Card: Outstanding Invoices --}}
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-start justify-between">
                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-amber-50 transition-transform duration-300 group-hover:scale-110">
                    <x-icon name="exclamation-circle" class="h-7 w-7 text-amber-600" />
                </div>
                <div class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-600">
                    Attention
                </div>
            </div>
            <div class="mt-6">
                <h3 class="text-3xl font-bold tracking-tight text-brand-text dark:text-white">${{ number_format($outstandingInvoiceAmount, 2) }}</h3>
                <p class="mt-1 text-sm font-medium text-brand-muted dark:text-slate-400">Outstanding Invoices</p>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-amber-500 to-amber-300 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
        </div>
    </div>

    {{-- Charts Section - Modernized --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        {{-- Revenue Trend Chart --}}
        <div class="col-span-12 xl:col-span-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-heading text-lg font-semibold text-brand-text dark:text-white">Revenue Trend</h4>
                        <p class="mt-1 text-sm text-brand-muted">Last 6 months performance</p>
                    </div>
                    <div class="flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-1.5">
                        <span class="block h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span class="text-sm font-medium text-emerald-600">Total Revenue</span>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="h-80">
                    <canvas id="adminRevenueTrendChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Request Status Distribution --}}
        <div class="col-span-12 xl:col-span-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-700">
                <h4 class="font-heading text-lg font-semibold text-brand-text dark:text-white">Request Status</h4>
                <p class="mt-1 text-sm text-brand-muted">Current distribution</p>
            </div>
            <div class="p-6">
                <div class="h-64">
                    <canvas id="adminRequestStatusChart"></canvas>
                </div>
                <div class="mt-6 space-y-3 max-h-48 overflow-y-auto">
                    @foreach($openRequestsByStatus as $status => $count)
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 transition-colors hover:bg-slate-100 dark:bg-slate-700/50 dark:hover:bg-slate-700">
                            <div class="flex items-center gap-3">
                                <span class="block h-3 w-3 rounded-full bg-brand-primary"></span>
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                            </div>
                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-bold text-brand-primary dark:bg-slate-800">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions - Modernized --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-gradient-to-br from-brand-primary to-brand-primary/90 p-6 shadow-sm">
        <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div>
                <h4 class="font-heading text-xl font-semibold text-white">Quick Actions</h4>
                <p class="mt-1 text-sm text-brand-secondary/90">Common administrative tasks</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.clients.create') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-brand-primary shadow-sm transition-all hover:shadow-md hover:scale-105">
                    <x-icon name="plus" class="h-4 w-4" />
                    <span>New Client</span>
                </a>
                <a href="{{ route('admin.invoices.create') }}"
                    class="inline-flex items-center gap-2 rounded-lg border-2 border-white px-5 py-2.5 text-sm font-semibold text-white transition-all hover:bg-white hover:text-brand-primary">
                    <x-icon name="document" class="h-4 w-4" />
                    <span>New Invoice</span>
                </a>
                <a href="{{ route('admin.requests.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition-all hover:bg-white/20">
                    <x-icon name="clipboard-list" class="h-4 w-4" />
                    <span>Assign Requests</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Tables Section - Modernized --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        {{-- Top Clients --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-700">
                <div class="flex items-center gap-2">
                    <x-icon name="office-building" class="h-5 w-5 text-brand-primary" />
                    <h4 class="font-heading text-lg font-semibold text-brand-text dark:text-white">Top Clients by Revenue</h4>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/50">
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-brand-muted">Client Name</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-brand-muted">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @forelse($topClients as $c)
                            <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-primary/10">
                                            <span class="text-sm font-bold text-brand-primary">{{ substr($c['company'], 0, 1) }}</span>
                                        </div>
                                        <span class="font-medium text-brand-text dark:text-white">{{ $c['company'] }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-sm font-bold text-emerald-600">
                                        <x-icon name="currency-dollar" class="h-4 w-4" />
                                        {{ number_format((float) $c['total'], 2) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-6 py-12 text-center">
                                    <x-icon name="inbox" class="mx-auto h-12 w-12 text-slate-300" />
                                    <p class="mt-2 text-sm text-brand-muted">No revenue data available</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Overdue Invoices --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-icon name="exclamation-circle" class="h-5 w-5 text-red-500" />
                        <h4 class="font-heading text-lg font-semibold text-brand-text dark:text-white">Overdue Invoices</h4>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-600">
                        <x-icon name="clock" class="h-3 w-3" />
                        {{ count($overdueInvoices) }} Pending
                    </span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/50">
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-brand-muted">Invoice</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-brand-muted">Client</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-brand-muted">Due Date</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-brand-muted">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @forelse($overdueInvoices as $inv)
                            <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                <td class="px-6 py-4">
                                    <span class="font-mono text-sm font-medium text-brand-text dark:text-white">{{ $inv['invoice_number'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-brand-muted">{{ Str::limit($inv['client'], 20) }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600">
                                        <x-icon name="clock" class="h-3 w-3" />
                                        {{ $inv['due_date'] ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="font-bold text-brand-text dark:text-white">${{ number_format((float) $inv['amount'], 2) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <x-icon name="check-circle" class="mx-auto h-12 w-12 text-emerald-300" />
                                    <p class="mt-2 text-sm font-medium text-emerald-600">Great job! No overdue invoices.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Recent Activity - Modernized --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-700">
            <div class="flex items-center gap-2">
                <x-icon name="clock" class="h-5 w-5 text-brand-primary" />
                <h4 class="font-heading text-lg font-semibold text-brand-text dark:text-white">Recent Activity</h4>
            </div>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                @forelse($recentActivity as $a)
                    <div class="flex items-start gap-4 rounded-lg border border-slate-100 bg-slate-50 p-4 transition-all hover:border-brand-secondary hover:shadow-sm dark:border-slate-700 dark:bg-slate-900/50">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-primary to-brand-primary/80">
                            <x-icon name="check-circle" class="h-6 w-6 text-white" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <h5 class="font-medium text-brand-text dark:text-white">{{ $a['description'] }}</h5>
                                <span class="shrink-0 text-xs text-brand-muted">{{ $a['when'] }}</span>
                            </div>
                            <div class="mt-1 flex items-center gap-2 text-sm text-brand-muted">
                                <x-icon name="user" class="h-4 w-4" />
                                <span>{{ $a['user'] }}</span>
                                <span class="text-slate-300">•</span>
                                <span>{{ $a['client'] ?? 'System' }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center">
                        <x-icon name="inbox" class="mx-auto h-16 w-16 text-slate-300" />
                        <p class="mt-4 text-sm text-brand-muted">No recent activity detected</p>
                    </div>
                @endforelse
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

                const rEl = document.getElementById('adminRevenueTrendChart');
                const sEl = document.getElementById('adminRequestStatusChart');

                if (!sEl || !rEl) return;

                // Cleanup existing charts
                ['status', 'revenue'].forEach(key => {
                    if (window.__adminCharts[key]) {
                        window.__adminCharts[key].destroy();
                        window.__adminCharts[key] = null;
                    }
                });

                // Brand colors
                const brandPrimary = '#5F5F82';
                const brandSecondary = '#C8D7EA';

                // Revenue Chart - Modern Area Chart with Brand Colors
                const ctxRev = rEl.getContext('2d');
                const gradientRev = ctxRev.createLinearGradient(0, 0, 0, 300);
                gradientRev.addColorStop(0, 'rgba(95, 95, 130, 0.2)'); // brand-primary with opacity
                gradientRev.addColorStop(1, 'rgba(95, 95, 130, 0)');

                window.__adminCharts.revenue = new Chart(ctxRev, {
                    type: 'line',
                    data: {
                        labels: revenue.labels || [],
                        datasets: [{
                            label: 'Revenue',
                            data: revenue.values || [],
                            borderColor: brandPrimary,
                            backgroundColor: gradientRev,
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: brandPrimary,
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: brandPrimary,
                            pointHoverBorderColor: '#fff',
                            pointHoverBorderWidth: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#fff',
                                titleColor: '#1E293B',
                                bodyColor: '#64748B',
                                borderColor: '#E2E8F0',
                                borderWidth: 1,
                                padding: 12,
                                displayColors: false,
                                callbacks: {
                                    label: (context) => ' Revenue: $' + context.parsed.y.toLocaleString()
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false, drawBorder: false },
                                ticks: { color: '#64748B', font: { size: 12 } }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#F1F5F9',
                                    borderDash: [3, 3],
                                    drawBorder: false
                                },
                                ticks: {
                                    color: '#64748B',
                                    font: { size: 12 },
                                    callback: (v) => '$' + v.toLocaleString()
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'nearest',
                        },
                    }
                });

                // Request Status Chart - Modern Doughnut with Brand Colors
                const statusColors = [
                    brandPrimary,
                    brandSecondary,
                    '#818CF8', // Indigo-400
                    '#A8B3C8', // Brand accent
                    '#64748B', // Brand muted
                ];

                window.__adminCharts.status = new Chart(sEl.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: status.labels || [],
                        datasets: [{
                            data: status.values || [],
                            backgroundColor: statusColors,
                            borderWidth: 0,
                            hoverOffset: 8,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#fff',
                                titleColor: '#1E293B',
                                bodyColor: '#64748B',
                                borderColor: '#E2E8F0',
                                borderWidth: 1,
                                padding: 12,
                            }
                        },
                        cutout: '70%',
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', initAdminCharts);
            document.addEventListener('livewire:initialized', initAdminCharts);
        })();
    </script>
@endpush
