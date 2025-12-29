<div>
    <div class="row">
        <div class="col-md-3">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>@money($unpaid)</h3>
                    <p>Unpaid invoices</p>
                </div>
                <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $completionRate }}%</h3>
                    <p>Request completion rate</p>
                </div>
                <div class="icon"><i class="fas fa-clipboard-check"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $avgResponseHours ? number_format((float) $avgResponseHours, 1) : '—' }}</h3>
                    <p>Avg response time (hrs)</p>
                </div>
                <div class="icon"><i class="fas fa-stopwatch"></i></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-line mr-1"></i> Monthly spending trend</h3>
        </div>
        <div class="card-body">
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

