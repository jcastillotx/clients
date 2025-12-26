<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-hdd mr-1"></i> Storage Usage by Client</h3>
            </div>
            <div class="card-body">
                <canvas id="storageUsageByClientChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-alt mr-1"></i> File Type Distribution</h3>
            </div>
            <div class="card-body">
                <canvas id="storageFileTypesChart" height="180"></canvas>
            </div>
        </div>
    </div>
</div>

@include('livewire.admin.reports._tables', ['tables' => $payload['tables'] ?? []])

