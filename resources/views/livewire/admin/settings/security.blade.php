<div class="row">
    <div class="col-md-6">
        <h5 class="mb-3">Authentication</h5>
        <div class="custom-control custom-switch mb-3">
            <input type="checkbox" class="custom-control-input" id="enforce_2fa" wire:model.defer="security.enforce_2fa">
            <label class="custom-control-label" for="enforce_2fa">Enforce Two-Factor Authentication</label>
        </div>
        <div class="alert alert-info">
            2FA enforcement is stored here; implementing the actual 2FA flow requires an authentication feature (e.g. TOTP) if not already present.
        </div>

        <h5 class="mt-4 mb-3">Password policy</h5>
        <div class="form-group">
            <label class="mb-1">Minimum length</label>
            <input type="number" class="form-control" wire:model.defer="security.password_min_length" min="6" max="128">
        </div>
        <div class="custom-control custom-switch mb-2">
            <input type="checkbox" class="custom-control-input" id="pw_symbols" wire:model.defer="security.password_require_symbols">
            <label class="custom-control-label" for="pw_symbols">Require symbols</label>
        </div>
        <div class="form-group">
            <label class="mb-1">Expiration (days)</label>
            <input type="number" class="form-control" wire:model.defer="security.password_expiration_days" min="0">
            <small class="text-muted">0 = never expires</small>
        </div>
    </div>

    <div class="col-md-6">
        <h5 class="mb-3">Sessions & Limits</h5>
        <div class="form-group">
            <label class="mb-1">Session timeout (minutes)</label>
            <input type="number" class="form-control" wire:model.defer="security.session_timeout_minutes" min="5" max="10080">
        </div>
        <div class="form-group">
            <label class="mb-1">Login attempt limit</label>
            <input type="number" class="form-control" wire:model.defer="security.login_max_attempts" min="1" max="100">
        </div>
        <div class="form-group">
            <label class="mb-1">API rate limit (per minute)</label>
            <input type="number" class="form-control" wire:model.defer="security.api_rate_limit_per_minute" min="1" max="10000">
        </div>

        <h5 class="mt-4 mb-3">IP access</h5>
        <div class="form-group">
            <label class="mb-1">Whitelist (comma separated)</label>
            <textarea class="form-control" rows="2" wire:model.defer="security.ip_whitelist" placeholder="1.2.3.4, 10.0.0.0/8"></textarea>
        </div>
        <div class="form-group">
            <label class="mb-1">Blacklist (comma separated)</label>
            <textarea class="form-control" rows="2" wire:model.defer="security.ip_blacklist" placeholder="5.6.7.8, 192.168.1.0/24"></textarea>
        </div>
    </div>
</div>

<button class="btn btn-primary" wire:click="saveSecurity">
    <i class="fas fa-save mr-1"></i> Save Security Settings
</button>

