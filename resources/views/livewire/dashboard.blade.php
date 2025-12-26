<div class="space-y-6">
    <!-- Loading overlay -->
    <div
        wire:loading.flex
        class="fixed inset-0 z-50 items-center justify-center bg-slate-900/20 backdrop-blur-sm"
        aria-label="Loading"
    >
        <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-lg ring-1 ring-black/5">
            <svg class="h-5 w-5 animate-spin text-slate-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span class="text-sm font-medium text-slate-700">Loading dashboard…</span>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-sm font-medium text-slate-500">Active Requests</div>
                    <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $activeRequests }}</div>
                </div>
                <div class="rounded-xl bg-slate-900/5 p-3 text-slate-900">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6M9 9h6M9 13h6M5 5h.01M5 9h.01M5 13h.01M7 17h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('requests.index') }}" class="text-sm font-semibold text-slate-900 hover:underline">View requests</a>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-sm font-medium text-slate-500">Pending Invoices</div>
                    <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $pendingInvoices }}</div>
                </div>
                <div class="rounded-xl bg-slate-900/5 p-3 text-slate-900">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2 2 4-4M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('invoices.index') }}" class="text-sm font-semibold text-slate-900 hover:underline">View invoices</a>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-sm font-medium text-slate-500">Active Contracts</div>
                    <div class="mt-2 text-3xl font-semibold text-slate-900">{{ $activeContracts }}</div>
                </div>
                <div class="rounded-xl bg-slate-900/5 p-3 text-slate-900">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3h8v4M6 7h12v14H6V7z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('contracts.index') }}" class="text-sm font-semibold text-slate-900 hover:underline">View contracts</a>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Requests by status</div>
                    <div class="mt-1 text-xs text-slate-500">Current breakdown</div>
                </div>
            </div>
            <div class="mt-4">
                <canvas id="requestStatusChart" height="180"></canvas>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Invoice trends</div>
                    <div class="mt-1 text-xs text-slate-500">Billed vs paid (last 6 months)</div>
                </div>
            </div>
            <div class="mt-4">
                <canvas id="invoiceTrendChart" height="180"></canvas>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-slate-900">Monthly spending</div>
                <div class="mt-1 text-xs text-slate-500">Successful payments (last 6 months)</div>
            </div>
        </div>
        <div class="mt-4">
            <canvas id="monthlySpendChart" height="120"></canvas>
        </div>
    </div>

    <!-- Quick actions -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="text-base font-semibold text-slate-900">Quick actions</div>
                <div class="mt-1 text-sm text-slate-500">Common tasks to keep things moving.</div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('requests.create') }}" class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                    New Request
                </a>
                <a href="{{ route('invoices.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
                    Pay Invoice
                </a>
                <a href="{{ route('contracts.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
                    View Contracts
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Recent activity -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-900">Recent activity</h2>
                <span class="text-xs font-medium text-slate-500">Last 10</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($recentActivity as $item)
                    <div class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50 px-3 py-3">
                        <div class="mt-0.5 h-2.5 w-2.5 rounded-full bg-slate-900/60"></div>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-slate-900">
                                {{ $item->description }}
                            </div>
                            <div class="mt-0.5 text-xs text-slate-500">
                                {{ $item->created_at?->diffForHumans() }}
                                @if($item->user)
                                    · {{ $item->user->name }}
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">
                        No recent activity yet.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Upcoming deadlines -->
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Upcoming invoice due dates</h2>
                <div class="mt-4 space-y-3">
                    @forelse($upcomingInvoices as $invoice)
                        <a href="{{ route('invoices.show', $invoice) }}" class="block rounded-xl border border-slate-100 bg-white px-3 py-3 hover:bg-slate-50">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-900">{{ $invoice->invoice_number }}</div>
                                    <div class="text-xs text-slate-500">Due {{ $invoice->due_date?->format('M d, Y') }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-slate-900">@money($invoice->amount)</div>
                                    <div class="text-xs text-slate-500">{{ ucfirst($invoice->status) }}</div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">
                            No invoices due.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900">Contract expirations</h2>
                <div class="mt-4 space-y-3">
                    @forelse($upcomingContracts as $contract)
                        <a href="{{ route('contracts.show', $contract) }}" class="block rounded-xl border border-slate-100 bg-white px-3 py-3 hover:bg-slate-50">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-900">{{ $contract->title }}</div>
                                    <div class="text-xs text-slate-500">Ends {{ $contract->end_date?->format('M d, Y') }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-slate-900">
                                        {{ $contract->days_until_expiration ?? $contract->daysUntilExpiration() ?? '—' }}
                                    </div>
                                    <div class="text-xs text-slate-500">days</div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">
                            No upcoming expirations.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const data = {
            requestStatus: @json($requestStatusChart),
            invoiceTrend: @json($invoiceTrendChart),
            monthlySpend: @json($monthlySpendChart),
        };

        function initDashboardCharts() {
            if (!window.Chart) return;
            window.__portalCharts = window.__portalCharts || {};

            const rsEl = document.getElementById('requestStatusChart');
            const itEl = document.getElementById('invoiceTrendChart');
            const msEl = document.getElementById('monthlySpendChart');
            if (!rsEl || !itEl || !msEl) return;

            // Destroy previous instances (Livewire re-renders)
            for (const key of ['requestStatus', 'invoiceTrend', 'monthlySpend']) {
                if (window.__portalCharts[key]) {
                    try { window.__portalCharts[key].destroy(); } catch (e) {}
                    window.__portalCharts[key] = null;
                }
            }

            window.__portalCharts.requestStatus = new Chart(rsEl.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: data.requestStatus.labels || [],
                    datasets: [{
                        data: data.requestStatus.values || [],
                        backgroundColor: ['#0f172a', '#334155', '#64748b', '#94a3b8', '#22c55e', '#ef4444', '#f59e0b'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 10 } },
                        tooltip: { enabled: true },
                    },
                },
            });

            window.__portalCharts.invoiceTrend = new Chart(itEl.getContext('2d'), {
                type: 'line',
                data: {
                    labels: data.invoiceTrend.labels || [],
                    datasets: [
                        {
                            label: 'Billed',
                            data: data.invoiceTrend.billed || [],
                            borderColor: '#0f172a',
                            backgroundColor: 'rgba(15, 23, 42, 0.08)',
                            tension: 0.35,
                            fill: true,
                        },
                        {
                            label: 'Paid',
                            data: data.invoiceTrend.paid || [],
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34, 197, 94, 0.10)',
                            tension: 0.35,
                            fill: true,
                        }
                    ],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom' } },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: (v) => '$' + Number(v).toFixed(0) } },
                    },
                },
            });

            window.__portalCharts.monthlySpend = new Chart(msEl.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: data.monthlySpend.labels || [],
                    datasets: [{
                        label: 'Spend',
                        data: data.monthlySpend.values || [],
                        backgroundColor: 'rgba(15, 23, 42, 0.85)',
                        borderRadius: 8,
                    }],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: (v) => '$' + Number(v).toFixed(0) } },
                    },
                },
            });
        }

        document.addEventListener('DOMContentLoaded', initDashboardCharts);
        document.addEventListener('livewire:initialized', initDashboardCharts);
    })();
</script>
@endpush

