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

<div class="mt-6">
    <button type="button" wire:click="savePayment" wire:loading.attr="disabled" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors flex items-center gap-2">
        <span wire:loading.remove wire:target="savePayment">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z" />
            </svg>
            Save Payment Settings
        </span>
        <span wire:loading wire:target="savePayment">
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Saving...
        </span>
    </button>
</div>

