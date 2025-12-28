<div class="row">
    <div class="col-md-6">
        <h5 class="mb-3">Authentication</h5>
        <div class="custom-control custom-switch mb-3">
            <input type="checkbox" class="custom-control-input" id="enforce_2fa" wire:model="security.enforce_2fa">
            <label class="custom-control-label" for="enforce_2fa">Enforce Two-Factor Authentication</label>
        </div>
        <div class="alert alert-info">
            2FA enforcement is stored here; implementing the actual 2FA flow requires an authentication feature (e.g. TOTP) if not already present.
        </div>

        <h5 class="mt-4 mb-3">Password policy</h5>
        <div class="form-group">
            <label class="mb-1">Minimum length</label>
            <input type="number" class="form-control" wire:model="security.password_min_length" min="6" max="128">
        </div>
        <div class="custom-control custom-switch mb-2">
            <input type="checkbox" class="custom-control-input" id="pw_symbols" wire:model="security.password_require_symbols">
            <label class="custom-control-label" for="pw_symbols">Require symbols</label>
        </div>
        <div class="form-group">
            <label class="mb-1">Expiration (days)</label>
            <input type="number" class="form-control" wire:model="security.password_expiration_days" min="0">
            <small class="text-muted">0 = never expires</small>
        </div>
    </div>

    <div class="col-md-6">
        <h5 class="mb-3">Sessions & Limits</h5>
        <div class="form-group">
            <label class="mb-1">Session timeout (minutes)</label>
            <input type="number" class="form-control" wire:model="security.session_timeout_minutes" min="5" max="10080">
        </div>
        <div class="form-group">
            <label class="mb-1">Login attempt limit</label>
            <input type="number" class="form-control" wire:model="security.login_max_attempts" min="1" max="100">
        </div>
        <div class="form-group">
            <label class="mb-1">API rate limit (per minute)</label>
            <input type="number" class="form-control" wire:model="security.api_rate_limit_per_minute" min="1" max="10000">
        </div>

        <h5 class="mt-4 mb-3">IP access</h5>
        <div class="form-group">
            <label class="mb-1">Whitelist (comma separated)</label>
            <textarea class="form-control" rows="2" wire:model="security.ip_whitelist" placeholder="1.2.3.4, 10.0.0.0/8"></textarea>
        </div>
        <div class="form-group">
            <label class="mb-1">Blacklist (comma separated)</label>
            <textarea class="form-control" rows="2" wire:model="security.ip_blacklist" placeholder="5.6.7.8, 192.168.1.0/24"></textarea>
        </div>
    </div>
</div>

<div class="mt-6">
    <button type="button" wire:click="saveSecurity" wire:loading.attr="disabled" class="btn-primary-modern">
        <span wire:loading.remove wire:target="saveSecurity">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z" />
            </svg>
            Save Security Settings
        </span>
        <span wire:loading wire:target="saveSecurity">
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Saving...
        </span>
    </button>
</div>

