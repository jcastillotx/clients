<div class="space-y-6">
    <!-- Header KPI Cards -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
        <!-- Card: Total Active Clients -->
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-center justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 dark:bg-slate-700">
                    <i class="fas fa-users text-xl text-blue-600"></i>
                </div>
                <!-- Percentage indicator (mock logic for demo, or real if available) -->
                @php $clientGrowth = 2.5; @endphp
                <span class="flex items-center gap-1 text-sm font-medium {{ $clientGrowth >= 0 ? 'text-emerald-500' : 'text-red-500' }}">
                    {{ abs($clientGrowth) }}%
                    <i class="fas fa-arrow-{{ $clientGrowth >= 0 ? 'up' : 'down' }}"></i>
                </span>
            </div>
            <div class="mt-4">
                <h4 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $activeClients }}</h4>
                <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Active Clients</span>
            </div>
        </div>

        <!-- Card: Open Requests -->
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-center justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 dark:bg-slate-700">
                    <i class="fas fa-tasks text-xl text-indigo-600"></i>
                </div>
                <div class="flex -space-x-2 overflow-hidden">
                    <!-- Avatars of assignees could go here, for now using counts -->
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 ring-2 ring-white text-xs text-slate-500 font-bold dark:ring-slate-800">
                        {{ array_sum($openRequestsByStatus) }}
                    </span>
                </div>
            </div>
            <div class="mt-4">
                <h4 class="text-2xl font-bold text-slate-800 dark:text-white">{{ array_sum($openRequestsByStatus) }}</h4>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Open Requests</span>
                    <span class="text-xs font-medium text-indigo-500">Pending Action</span>
                </div>
            </div>
        </div>

        <!-- Card: Revenue -->
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-center justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 dark:bg-slate-700">
                    <i class="fas fa-dollar-sign text-xl text-emerald-600"></i>
                </div>
                @php
                    $revChange = $revenueLastMonth > 0 ? (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100 : 100;
                @endphp
                <span class="flex items-center gap-1 text-sm font-medium {{ $revChange >= 0 ? 'text-emerald-500' : 'text-red-500' }}">
                    {{ number_format(abs($revChange), 1) }}%
                    <i class="fas fa-arrow-{{ $revChange >= 0 ? 'up' : 'down' }}"></i>
                </span>
            </div>
            <div class="mt-4">
                <h4 class="text-2xl font-bold text-slate-800 dark:text-white">${{ number_format($revenueThisMonth, 2) }}</h4>
                <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Revenue (This Month)</span>
            </div>
        </div>

        <!-- Card: Outstanding Invoices -->
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-center justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 dark:bg-slate-700">
                    <i class="fas fa-file-invoice-dollar text-xl text-amber-600"></i>
                </div>
                <span class="flex items-center gap-1 text-sm font-medium text-amber-500">
                    Needs Attention
                </span>
            </div>
            <div class="mt-4">
                <h4 class="text-2xl font-bold text-slate-800 dark:text-white">${{ number_format($outstandingInvoiceAmount, 2) }}</h4>
                <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Outstanding Invoices</span>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <!-- Revenue Trend Chart -->
        <div class="col-span-12 xl:col-span-8 rounded-lg border border-slate-200 bg-white px-5 pt-7.5 pb-5 shadow-sm dark:border-slate-700 dark:bg-slate-800 sm:px-7.5">
            <div class="flex flex-wrap items-start justify-between gap-3 sm:flex-nowrap">
                <div class="flex w-full flex-wrap gap-3 sm:gap-5">
                    <div class="flex min-w-47.5">
                        <span class="mr-2 mt-1 flex h-4 w-full max-w-4 items-center justify-center rounded-full border border-emerald-500">
                            <span class="block h-2.5 w-full max-w-2.5 rounded-full bg-emerald-500"></span>
                        </span>
                        <div class="w-full">
                            <p class="font-semibold text-emerald-500">Total Revenue</p>
                            <p class="text-sm font-medium text-slate-500">Last 6 Months</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-4 h-80"> <!-- Fixed height for chart -->
                <canvas id="adminRevenueTrendChart"></canvas>
            </div>
        </div>

        <!-- Request Status Funnel -->
        <div class="col-span-12 xl:col-span-4 rounded-lg border border-slate-200 bg-white px-5 pt-7.5 pb-5 shadow-sm dark:border-slate-700 dark:bg-slate-800 sm:px-7.5">
            <div class="mb-3 justify-between gap-4 sm:flex">
                <div>
                    <h5 class="text-xl font-semibold text-slate-800 dark:text-white">Request Status</h5>
                </div>
            </div>
            <div class="mb-2">
                <div id="chartThree" class="mx-auto flex justify-center h-64"> <!-- Fixed height circle/bar -->
                    <canvas id="adminRequestStatusChart"></canvas>
                </div>
            </div>
            <div class="flex flex-col gap-2 mt-4 max-h-48 overflow-y-auto">
                @foreach($openRequestsByStatus as $status => $count)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="block h-3 w-3 rounded-full bg-blue-600"></span>
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                        </div>
                        <span class="text-sm font-semibold text-slate-800 dark:text-white">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h4 class="text-lg font-semibold text-slate-800 dark:text-white">Quick Actions</h4>
                <p class="text-sm text-slate-500">Common administrative tasks</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.clients.create') }}" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-blue-600 px-6 py-3 text-center text-sm font-medium text-white hover:bg-opacity-90 transition-all">
                    <i class="fas fa-plus"></i>
                    New Client
                </a>
                <a href="{{ route('admin.invoices.create') }}" class="inline-flex items-center justify-center gap-2.5 rounded-md border border-blue-600 px-6 py-3 text-center text-sm font-medium text-blue-600 hover:bg-blue-600 hover:text-white transition-all">
                    <i class="fas fa-file-invoice"></i>
                    New Invoice
                </a>
                <a href="{{ route('admin.requests.index') }}" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-slate-100 px-6 py-3 text-center text-sm font-medium text-slate-800 hover:bg-slate-200 dark:bg-slate-700 dark:text-white transition-all">
                    <i class="fas fa-tasks"></i>
                    Assign Requests
                </a>
            </div>
        </div>
    </div>

    <!-- Tables Section -->
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <!-- Top Clients -->
        <div class="rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                <h4 class="text-lg font-semibold text-slate-800 dark:text-white">Top Clients by Revenue</h4>
            </div>
            <div class="p-4">
                <div class="flex flex-col">
                    <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="inline-block min-w-full py-2 sm:px-6 lg:px-8">
                            <table class="min-w-full text-left text-sm font-light">
                                <thead class="font-medium text-slate-500 dark:text-slate-400">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">Client Name</th>
                                        <th scope="col" class="px-6 py-3 text-right">Total Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topClients as $c)
                                        <tr class="border-b transition duration-300 ease-in-out hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700">
                                            <td class="whitespace-nowrap px-6 py-4 font-medium text-slate-800 dark:text-white">
                                                {{ $c['company'] }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-right font-bold text-emerald-600">
                                                ${{ number_format((float) $c['total'], 2) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="px-6 py-8 text-center text-slate-500">No revenue data available.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overdue Invoices -->
        <div class="rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <h4 class="text-lg font-semibold text-slate-800 dark:text-white">Overdue Invoices</h4>
                    <span class="inline-flex items-center justify-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">
                        {{ count($overdueInvoices) }} Pending
                    </span>
                </div>
            </div>
            <div class="p-4">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-500 dark:text-slate-400">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-700 dark:bg-slate-700 dark:text-slate-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">Invoice</th>
                                <th scope="col" class="px-6 py-3">Client</th>
                                <th scope="col" class="px-6 py-3">Due Date</th>
                                <th scope="col" class="px-6 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            @forelse($overdueInvoices as $inv)
                                <tr class="bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-700">
                                    <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">
                                        {{ $inv['invoice_number'] }}
                                    </td>
                                    <td class="px-6 py-4">{{ Str::limit($inv['client'], 20) }}</td>
                                    <td class="px-6 py-4 text-red-500">{{ $inv['due_date'] ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-right font-medium text-slate-900 dark:text-white">
                                        ${{ number_format((float) $inv['amount'], 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-slate-500">Great job! No overdue invoices.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
            <h4 class="text-lg font-semibold text-slate-800 dark:text-white">Recent Activity</h4>
        </div>
        <div class="p-6">
            <div class="flex flex-col gap-4">
                @forelse($recentActivity as $a)
                    <div class="flex items-start gap-4 pb-4 border-b border-slate-100 last:border-0 last:pb-0 dark:border-slate-700">
                        <div class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700">
                            <i class="fas fa-history text-slate-500 dark:text-slate-300"></i>
                        </div>
                        <div class="w-full">
                            <div class="mb-1 flex justify-between">
                                <h5 class="font-medium text-slate-800 dark:text-white">{{ $a['description'] }}</h5>
                                <span class="text-xs text-slate-400">{{ $a['when'] }}</span>
                            </div>
                            <p class="text-sm text-slate-500">{{ $a['user'] }} <span class="mx-1 text-slate-300">|</span> {{ $a['client'] ?? 'System' }}</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-slate-500">No recent activity detected.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const calculateTrend = (data) => {
            // Simple visual enhancement for chart lines
            return data;
        };

        const status = @json($requestStatusChart);
        const revenue = @json($revenueTrendChart);

        function initAdminCharts() {
            if (!window.Chart) return;
            window.__adminCharts = window.__adminCharts || {};

            const rEl = document.getElementById('adminRevenueTrendChart');
            const sEl = document.getElementById('adminRequestStatusChart');
            
            if (!sEl || !rEl) return;

            // Cleanup
            ['status', 'revenue'].forEach(key => {
                if (window.__adminCharts[key]) {
                    window.__adminCharts[key].destroy();
                    window.__adminCharts[key] = null;
                }
            });

            // Revenue Chart - Area Spline Style
            const ctxRev = rEl.getContext('2d');
            const gradientRev = ctxRev.createLinearGradient(0, 0, 0, 300);
            gradientRev.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
            gradientRev.addColorStop(1, 'rgba(16, 185, 129, 0)');

            window.__adminCharts.revenue = new Chart(ctxRev, {
                type: 'line',
                data: {
                    labels: revenue.labels || [],
                    datasets: [{
                        label: 'Revenue',
                        data: revenue.values || [],
                        borderColor: '#10B981', // Emerald 500
                        backgroundColor: gradientRev,
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#10B981',
                        pointHoverBackgroundColor: '#10B981',
                        pointHoverBorderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: '#fff',
                            titleColor: '#1e293b',
                            bodyColor: '#475569',
                            borderColor: '#e2e8f0',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return ' Revenue: $' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: { color: '#64748b' }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f1f5f9',
                                borderDash: [2, 2],
                                drawBorder: false
                            },
                            ticks: {
                                color: '#64748b',
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

            // Request Status Chart - Doughnut for Funnel/Distribution (Better Visual)
            // Or Bar Chart (Request Funnel). Let's stick to Bar but styled cleaner.
            window.__adminCharts.status = new Chart(sEl.getContext('2d'), {
                type: 'doughnut', // Changed to Doughnut for nicer 'status distribution' look
                data: {
                    labels: status.labels || [],
                    datasets: [{
                        data: status.values || [],
                        backgroundColor: [
                            '#3C50E0', '#80CAEE', '#0FADCF', '#6577F3', '#0FADCF', '#F3F5F8'
                        ],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    cutout: '75%'
                }
            });
        }

        document.addEventListener('DOMContentLoaded', initAdminCharts);
        document.addEventListener('livewire:initialized', initAdminCharts);
    })();
</script>
@endpush