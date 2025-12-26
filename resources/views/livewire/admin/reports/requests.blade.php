<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tags mr-1"></i> Volume by Type</h3>
            </div>
            <div class="card-body">
                <canvas id="requestsByTypeChart" height="180"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tasks mr-1"></i> Volume by Status</h3>
            </div>
            <div class="card-body">
                <canvas id="requestsByStatusChart" height="180"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-1"></i> Volume by Priority</h3>
            </div>
            <div class="card-body">
                <canvas id="requestsByPriorityChart" height="180"></canvas>
            </div>
        </div>
    </div>
</div>

@include('livewire.admin.reports._tables', ['tables' => $payload['tables'] ?? []])

