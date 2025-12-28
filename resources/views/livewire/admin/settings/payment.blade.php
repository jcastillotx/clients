<div class="row">
    <div class="col-md-6">
        <h5 class="mb-3">Stripe</h5>
        <div class="form-group">
            <label class="mb-1">Mode</label>
            <select class="form-control" wire:model="payment.stripe_mode">
                <option value="test">Test</option>
                <option value="live">Live</option>
            </select>
        </div>
        <div class="form-group">
            <label class="mb-1">Test public key</label>
            <input class="form-control" wire:model="payment.stripe_test_public">
        </div>
        <div class="form-group">
            <label class="mb-1">Test secret key</label>
            <input type="password" class="form-control" wire:model="payment.stripe_test_secret">
        </div>
        <div class="form-group">
            <label class="mb-1">Live public key</label>
            <input class="form-control" wire:model="payment.stripe_live_public">
        </div>
        <div class="form-group">
            <label class="mb-1">Live secret key</label>
            <input type="password" class="form-control" wire:model="payment.stripe_live_secret">
        </div>
    </div>

    <div class="col-md-6">
        <h5 class="mb-3">PayPal</h5>
        <div class="form-group">
            <label class="mb-1">Client ID</label>
            <input class="form-control" wire:model="payment.paypal_client_id">
        </div>
        <div class="form-group">
            <label class="mb-1">Secret</label>
            <input type="password" class="form-control" wire:model="payment.paypal_secret">
        </div>

        <h5 class="mt-4 mb-3">Terms & Fees</h5>
        <div class="form-group">
            <label class="mb-1">Default payment terms</label>
            <select class="form-control" wire:model="payment.payment_terms">
                <option value="Net 15">Net 15</option>
                <option value="Net 30">Net 30</option>
                <option value="Net 45">Net 45</option>
                <option value="Due on receipt">Due on receipt</option>
            </select>
        </div>
        <div class="custom-control custom-switch mb-2">
            <input type="checkbox" class="custom-control-input" id="late_fee_enabled" wire:model="payment.late_fee_enabled">
            <label class="custom-control-label" for="late_fee_enabled">Late fee enabled</label>
        </div>
        <div class="form-group">
            <label class="mb-1">Late fee percent</label>
            <input type="number" class="form-control" wire:model="payment.late_fee_percent" step="0.01">
        </div>
        <div class="form-group">
            <label class="mb-1">Tax rate (%)</label>
            <input type="number" class="form-control" wire:model="payment.tax_rate" step="0.01">
        </div>

        <h5 class="mt-4 mb-2">Accepted payment methods</h5>
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="pm_card" value="card" wire:model="payment.accepted_methods">
            <label class="custom-control-label" for="pm_card">Card</label>
        </div>
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="pm_ach" value="ach" wire:model="payment.accepted_methods">
            <label class="custom-control-label" for="pm_ach">ACH</label>
        </div>
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="pm_paypal" value="paypal" wire:model="payment.accepted_methods">
            <label class="custom-control-label" for="pm_paypal">PayPal</label>
        </div>
    </div>
</div>

<button class="btn btn-primary" wire:click="savePayment">
    <i class="fas fa-save mr-1"></i> Save Payment Settings
</button>

