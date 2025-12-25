<form wire:submit.prevent="savePayment" class="vstack gap-3">
    <div>
        <div class="h3 mb-1">Stripe</div>
        <div class="text-muted small">Keys are stored in the database (secrets encrypted). Use mode toggle to pick test/live.</div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-4">
            <label class="form-label">Mode</label>
            <select class="form-select" wire:model.defer="state.payments.mode">
                <option value="test">Test</option>
                <option value="live">Live</option>
            </select>
            @error('state.payments.mode')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label">Default payment terms</label>
            <select class="form-select" wire:model.defer="state.payments.default_terms">
                <option value="net_15">Net 15</option>
                <option value="net_30">Net 30</option>
                <option value="net_45">Net 45</option>
                <option value="net_60">Net 60</option>
                <option value="due_on_receipt">Due on receipt</option>
            </select>
            @error('state.payments.default_terms')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label">Tax rate (%)</label>
            <input type="number" step="0.01" class="form-control" wire:model.defer="state.payments.tax_rate">
            @error('state.payments.tax_rate')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card bg-transparent border">
                <div class="card-body">
                    <div class="fw-semibold mb-2">Stripe test</div>
                    <div class="mb-3">
                        <label class="form-label">Publishable key</label>
                        <input class="form-control" wire:model.defer="state.stripe.test_key">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Secret key</label>
                        <input class="form-control" type="password" wire:model.defer="state.stripe.test_secret" autocomplete="new-password">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card bg-transparent border">
                <div class="card-body">
                    <div class="fw-semibold mb-2">Stripe live</div>
                    <div class="mb-3">
                        <label class="form-label">Publishable key</label>
                        <input class="form-control" wire:model.defer="state.stripe.live_key">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Secret key</label>
                        <input class="form-control" type="password" wire:model.defer="state.stripe.live_secret" autocomplete="new-password">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-2">

    <div>
        <div class="h3 mb-1">PayPal (optional)</div>
        <div class="text-muted small">Stored in DB (secret encrypted).</div>
    </div>
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <label class="form-label">Client ID</label>
            <input class="form-control" wire:model.defer="state.paypal.client_id">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Secret</label>
            <input class="form-control" type="password" wire:model.defer="state.paypal.secret" autocomplete="new-password">
        </div>
    </div>

    <hr class="my-2">

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">Late fees</div>
            <label class="form-check mt-2">
                <input class="form-check-input" type="checkbox" wire:model.defer="state.payments.late_fee.enabled">
                <span class="form-check-label">Enable automatic late fee calculation</span>
            </label>
            <div class="mt-2">
                <label class="form-label">Late fee percent (%)</label>
                <input type="number" step="0.01" class="form-control" wire:model.defer="state.payments.late_fee.percent">
                @error('state.payments.late_fee.percent')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">Accepted payment methods</div>
            <div class="text-muted small mb-2">Controls what’s offered to clients.</div>
            <div class="vstack gap-2">
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" value="stripe" wire:model.defer="state.payments.methods">
                    <span class="form-check-label">Card (Stripe)</span>
                </label>
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" value="paypal" wire:model.defer="state.payments.methods">
                    <span class="form-check-label">PayPal</span>
                </label>
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" value="bank_transfer" wire:model.defer="state.payments.methods">
                    <span class="form-check-label">Bank transfer</span>
                </label>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button class="btn btn-primary" type="submit">Save payment settings</button>
    </div>
</form>

