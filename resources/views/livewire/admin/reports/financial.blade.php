<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line mr-1"></i> Revenue Trend</h3>
            </div>
            <div class="card-body">
                <canvas id="revenueTrendChart" height="90"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        @php $pl = data_get($payload, 'tables.P&L Summary.0', []); @endphp
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-coins mr-1"></i> Financial Snapshot</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="text-muted">Profit</div>
                        <div class="h5 mb-3">@money(data_get($pl, 'profit', 0))</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted">Receivables</div>
                        <div class="h5 mb-3">@money(data_get($pl, 'outstanding_receivables', 0))</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted">Revenue</div>
                        <div class="h6 mb-0">@money(data_get($pl, 'total_revenue', 0))</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted">Costs (est.)</div>
                        <div class="h6 mb-0">@money(data_get($pl, 'estimated_costs', 0))</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-layer-group mr-1"></i> Revenue by Client Tier</h3>
            </div>
            <div class="card-body">
                <canvas id="revenueByTierChart" height="140"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-briefcase mr-1"></i> Revenue by Service Type</h3>
            </div>
            <div class="card-body">
                <canvas id="revenueByServiceTypeChart" height="140"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-hourglass-half mr-1"></i> Invoice Aging (Amount)</h3>
            </div>
            <div class="card-body">
                <canvas id="invoiceAgingChart" height="140"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-money-check-alt mr-1"></i> Payment Methods</h3>
    </div>
    <div class="card-body">
        <canvas id="paymentMethodsChart" height="110"></canvas>
    </div>
</div>

@include('livewire.admin.reports._tables', ['tables' => $payload['tables'] ?? []])

