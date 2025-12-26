<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-plus mr-1"></i> Client Acquisition</h3>
            </div>
            <div class="card-body">
                <canvas id="clientAcquisitionChart" height="110"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-layer-group mr-1"></i> Clients by Tier</h3>
            </div>
            <div class="card-body">
                <canvas id="clientsByTierChart" height="160"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-toggle-on mr-1"></i> Clients by Status</h3>
            </div>
            <div class="card-body">
                <canvas id="clientsByStatusChart" height="170"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-table mr-1"></i> Highlights</h3>
            </div>
            <div class="card-body">
                @include('livewire.admin.reports._tables', ['tables' => array_slice($payload['tables'] ?? [], 0, 2, true)])
            </div>
        </div>
    </div>
</div>

@include('livewire.admin.reports._tables', ['tables' => $payload['tables'] ?? []])

