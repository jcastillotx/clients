<div class="space-y-6">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-rose-500 to-rose-600 p-6 text-white shadow-sm">
            <div class="relative z-10">
                <h3 class="text-3xl font-bold">@money($unpaid)</h3>
                <p class="mt-1 text-sm text-rose-100">Unpaid invoices</p>
            </div>
            <div class="absolute bottom-4 right-4 text-6xl text-rose-400 opacity-20"><i class="fas fa-file-invoice-dollar"></i></div>
        </div>
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-white shadow-sm">
            <div class="relative z-10">
                <h3 class="text-3xl font-bold">{{ $completionRate }}%</h3>
                <p class="mt-1 text-sm text-blue-100">Request completion rate</p>
            </div>
            <div class="absolute bottom-4 right-4 text-6xl text-blue-400 opacity-20"><i class="fas fa-clipboard-check"></i></div>
        </div>
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 p-6 text-white shadow-sm">
            <div class="relative z-10">
                <h3 class="text-3xl font-bold">{{ $avgResponseHours ? number_format((float) $avgResponseHours, 1) : '—' }}</h3>
                <p class="mt-1 text-sm text-emerald-100">Avg response time (hrs)</p>
            </div>
            <div class="absolute bottom-4 right-4 text-6xl text-emerald-400 opacity-20"><i class="fas fa-stopwatch"></i></div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
            <h3 class="text-base font-semibold text-slate-900"><i class="fas fa-chart-line mr-1"></i> Monthly spending trend</h3>
        </div>
        <div class="p-4">
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
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: spend.map(x => x.ym),
                        datasets: [{
                            label: 'Spend',
                            data: spend.map(x => x.total),
                            borderColor: '#3c8dbc',
                            backgroundColor: 'rgba(60, 141, 188, 0.15)',
                            fill: true,
                            tension: 0.2,
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            }
        </script>
    @endpush

