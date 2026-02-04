<div class="space-y-6">
    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        {{-- Unpaid Invoices Card --}}
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-brand-primary/10 transition-transform duration-300 group-hover:scale-110">
                        <x-icon name="currency-dollar" class="h-7 w-7 text-brand-primary" />
                    </div>
                    <h3 class="mt-4 text-3xl font-bold font-heading tracking-tight text-brand-text">@money($unpaid)</h3>
                    <p class="mt-1 text-sm text-brand-muted">Unpaid invoices</p>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-brand-primary to-brand-secondary opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
        </div>

        {{-- Completion Rate Card --}}
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-brand-secondary/30 transition-transform duration-300 group-hover:scale-110">
                        <x-icon name="clipboard-check" class="h-7 w-7 text-brand-primary" />
                    </div>
                    <h3 class="mt-4 text-3xl font-bold font-heading tracking-tight text-brand-text">{{ $completionRate }}%</h3>
                    <p class="mt-1 text-sm text-brand-muted">Request completion rate</p>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-brand-secondary to-brand-accent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
        </div>

        {{-- Avg Response Time Card --}}
        <div class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-emerald-50 transition-transform duration-300 group-hover:scale-110">
                        <x-icon name="clock" class="h-7 w-7 text-emerald-600" />
                    </div>
                    <h3 class="mt-4 text-3xl font-bold font-heading tracking-tight text-brand-text">{{ $avgResponseHours ? number_format((float) $avgResponseHours, 1) : '—' }}</h3>
                    <p class="mt-1 text-sm text-brand-muted">Avg response time (hrs)</p>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-gradient-to-r from-emerald-400 to-emerald-600 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
        </div>
    </div>

    {{-- Monthly Spending Chart --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
            <div class="flex items-center gap-2">
                <x-icon name="chart-bar" class="h-5 w-5 text-brand-primary" />
                <h3 class="text-base font-semibold font-heading text-brand-text">Monthly spending trend</h3>
            </div>
        </div>
        <div class="p-6">
            <canvas id="spendChart" height="80"></canvas>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const spend = @json($spendByMonth->map(fn($r) => ['ym' => $r->ym, 'total' => (float) $r->total])->values());
    const ctx = document.getElementById('spendChart');
    if (ctx) {
        const brandPrimary = '#5F5F82';
        const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(95, 95, 130, 0.2)');
        gradient.addColorStop(1, 'rgba(95, 95, 130, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: spend.map(x => x.ym),
                datasets: [{
                    label: 'Spend',
                    data: spend.map(x => x.total),
                    borderColor: brandPrimary,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: brandPrimary,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(30, 41, 59, 0.95)',
                        titleColor: '#fff',
                        bodyColor: '#cbd5e1',
                        padding: 12,
                        borderColor: 'rgba(148, 163, 184, 0.2)',
                        borderWidth: 1,
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b', font: { size: 12 } },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            color: '#64748b',
                            font: { size: 12 },
                            callback: (v) => '$' + Number(v).toLocaleString(),
                        },
                    },
                },
            }
        });
    }
</script>
@endpush
