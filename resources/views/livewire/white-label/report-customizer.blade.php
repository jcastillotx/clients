<x-app-layout>
    <x-slot name="header">Client report settings</x-slot>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-sliders-h mr-1"></i> Report Customizer</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="mb-1">Client</label>
                <select class="form-control" wire:model="clientId" wire:change="loadClient">
                    <option value="">Select…</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-row">
                <div class="col-md-4">
                    <label class="mb-1">Frequency</label>
                    <select class="form-control" wire:model.defer="reportFrequency">
                        <option value="daily">daily</option>
                        <option value="weekly">weekly</option>
                        <option value="monthly">monthly</option>
                        <option value="quarterly">quarterly</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="mb-1">Delivery</label>
                    <select class="form-control" wire:model.defer="deliveryMethod">
                        <option value="email">email</option>
                        <option value="portal">portal</option>
                        <option value="both">both</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="mb-1">Recipients (CSV)</label>
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

            <button class="btn btn-primary mt-3" wire:click="save"><i class="fas fa-save mr-1"></i> Save</button>
        </div>
    </div>
</x-app-layout>

