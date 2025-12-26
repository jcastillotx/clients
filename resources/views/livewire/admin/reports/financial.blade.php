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
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-money-check-alt mr-1"></i> Payment Methods</h3>
            </div>
            <div class="card-body">
                <canvas id="paymentMethodsChart" height="180"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-layer-group mr-1"></i> Revenue by Client Tier</h3>
            </div>
            <div class="card-body">
                <canvas id="revenueByTierChart" height="140"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
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

@include('livewire.admin.reports._tables', ['tables' => $payload['tables'] ?? []])

