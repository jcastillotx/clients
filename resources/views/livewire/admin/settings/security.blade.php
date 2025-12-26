<form wire:submit.prevent="saveSecurity" class="vstack gap-3">
    <div>
        <div class="h3 mb-1">Security settings</div>
        <div class="text-muted small">Stored in DB (cached). Enforcement should be wired into auth/middleware.</div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">Two-factor authentication</div>
            <label class="form-check mt-2">
                <input class="form-check-input" type="checkbox" wire:model.defer="state.security.2fa_enforced">
                <span class="form-check-label">Enforce 2FA for admins</span>
            </label>
            <div class="text-muted small mt-1">Requires enforcement logic (e.g. middleware) to block non-2FA accounts.</div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">Session timeout</div>
            <label class="form-label">Session timeout (minutes)</label>
            <input type="number" class="form-control" wire:model.defer="state.security.session_timeout_minutes">
            @error('state.security.session_timeout_minutes')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>

    <hr class="my-2">

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">Password policy</div>
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label">Min length</label>
                    <input type="number" class="form-control" wire:model.defer="state.security.password.min_length">
                    @error('state.security.password.min_length')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-6">
                    <label class="form-label">Expiration (days)</label>
                    <input type="number" class="form-control" wire:model.defer="state.security.password.expire_days">
                    <div class="text-muted small mt-1">0 disables expiration.</div>
                    @error('state.security.password.expire_days')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" wire:model.defer="state.security.password.require_numbers">
                        <span class="form-check-label">Require numbers</span>
                    </label>
                    <label class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" wire:model.defer="state.security.password.require_symbols">
                        <span class="form-check-label">Require symbols</span>
                    </label>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">Login / API limits</div>
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label">Login attempt limit</label>
                    <input type="number" class="form-control" wire:model.defer="state.security.login_attempt_limit">
                    @error('state.security.login_attempt_limit')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="col-6">
                    <label class="form-label">API rate limit (/min)</label>
                    <input type="number" class="form-control" wire:model.defer="state.security.api_rate_limit_per_min">
                    @error('state.security.api_rate_limit_per_min')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <hr class="my-2">

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">IP allowlist</div>
            <div class="text-muted small mb-2">One per line (CIDR allowed). Example: <code>203.0.113.0/24</code></div>
            <textarea class="form-control" rows="6" wire:model.defer="state.security.ip_allowlist"></textarea>
        </div>
        <div class="col-12 col-xl-6">
            <div class="h3 mb-1">IP blocklist</div>
            <div class="text-muted small mb-2">One per line (CIDR allowed).</div>
            <textarea class="form-control" rows="6" wire:model.defer="state.security.ip_blocklist"></textarea>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button class="btn btn-primary" type="submit">Save security settings</button>
    </div>
</form>

