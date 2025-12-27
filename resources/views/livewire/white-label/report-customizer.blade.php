<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Client Report Customizer</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label>Client</label>
                <select class="form-control" wire:model="clientId" wire:change="loadClient">
                    <option value="">Select…</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-row">
                <div class="col-md-4">
                    <label>Frequency</label>
                    <select class="form-control" wire:model.defer="reportFrequency">
                        <option value="daily">daily</option>
                        <option value="weekly">weekly</option>
                        <option value="monthly">monthly</option>
                        <option value="quarterly">quarterly</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Delivery</label>
                    <select class="form-control" wire:model.defer="deliveryMethod">
                        <option value="email">email</option>
                        <option value="portal">portal</option>
                        <option value="both">both</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Recipients (CSV)</label>
                    <input class="form-control" wire:model.defer="recipientsCsv" placeholder="client@company.com, ...">
                </div>
            </div>

            <hr>

            <div class="mb-2 font-weight-bold">Visible metrics</div>
            <div class="d-flex flex-wrap" style="gap: 10px;">
                @foreach($availableMetrics as $m)
                    <label class="badge badge-light p-2" style="cursor:pointer;">
                        <input type="checkbox" wire:model.defer="visibleMetrics" value="{{ $m }}"> {{ $m }}
                    </label>
                @endforeach
            </div>

            <button class="btn btn-primary mt-3" wire:click="save">Save</button>
        </div>
    </div>
</div>

