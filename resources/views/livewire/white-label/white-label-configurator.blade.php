<div>
    <h2 class="mb-3">White label</h2>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-palette mr-1"></i> White Label Configurator</h3>
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

                    <div class="form-group">
                        <label class="mb-1">Custom domain</label>
                        <input class="form-control" placeholder="reports.clientdomain.com" wire:model.defer="customDomain">
                        <small class="text-muted">Point DNS CNAME to your portal host, then enable Active.</small>
                    </div>

                    <div class="form-group">
                        <label class="mb-1">Company name</label>
                        <input class="form-control" wire:model.defer="companyName">
                    </div>

                    <div class="form-group">
                        <label class="mb-1">Logo URL</label>
                        <input class="form-control" wire:model.defer="logoUrl">
                    </div>

                    <div class="form-row">
                        <div class="col">
                            <label class="mb-1">Primary color</label>
                            <input class="form-control" wire:model.defer="primaryColor">
                        </div>
                        <div class="col">
                            <label class="mb-1">Secondary color</label>
                            <input class="form-control" wire:model.defer="secondaryColor">
                        </div>
                    </div>

                    <div class="form-group mt-2">
                        <label class="mb-1">Footer text</label>
                        <textarea class="form-control" rows="2" wire:model.defer="footerText"></textarea>
                    </div>

                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" wire:model.defer="isActive" id="wlActive">
                        <label class="form-check-label" for="wlActive">Active</label>
                    </div>

                    <button class="btn btn-primary mt-3" wire:click="save"><i class="fas fa-save mr-1"></i> Save</button>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-eye mr-1"></i> Preview</h3>
                </div>
                <div class="card-body">
                    <div class="border rounded p-3" style="font-family: {{ $fontFamily }};">
                        <div class="d-flex align-items-center justify-content-between">
                            <strong>{{ $companyName ?: 'Company' }}</strong>
                            <span class="badge" style="background: {{ $primaryColor }}; color: #fff;">Primary</span>
                        </div>
                        <div class="text-muted small mt-1">Domain: {{ $customDomain ?: '(not set)' }}</div>
                        <hr>
                        <div class="text-muted small">Footer</div>
                        <div>{{ $footerText ?: '(none)' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

