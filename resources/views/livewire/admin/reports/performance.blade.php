<div class="row">
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-reply mr-1"></i> Response Time</h3>
            </div>
            <div class="card-body">
                <canvas id="performanceResponseChart" height="160"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-check mr-1"></i> Resolution Time</h3>
            </div>
            <div class="card-body">
                <canvas id="performanceResolutionChart" height="160"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-friends mr-1"></i> Staff Workload Distribution</h3>
            </div>
            <div class="card-body">
                <canvas id="performanceWorkloadChart" height="160"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-area mr-1"></i> Monthly Performance Trends</h3>
    </div>
    <div class="card-body">
        <canvas id="performanceMonthlyChart" height="90"></canvas>
    </div>
</div>

@include('livewire.admin.reports._tables', ['tables' => $payload['tables'] ?? []])

