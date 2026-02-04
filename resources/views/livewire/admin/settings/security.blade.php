<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="space-y-6">
        <div>
            <h5 class="text-base font-semibold text-slate-900 mb-4">Authentication</h5>
            <label class="flex items-center gap-3 cursor-pointer mb-4">
                <input type="checkbox"
                       class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0"
                       id="enforce_2fa"
                       wire:model="security.enforce_2fa">
                <span class="text-sm text-slate-700">Enforce Two-Factor Authentication</span>
            </label>
            <div class="rounded-xl bg-blue-50 border border-blue-200 p-4">
                <p class="text-sm text-blue-800">
                    2FA enforcement is stored here; implementing the actual 2FA flow requires an authentication feature (e.g. TOTP) if not already present.
                </p>
            </div>
        </div>

        <div>
            <h5 class="text-base font-semibold text-slate-900 mb-4">Password Policy</h5>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Minimum length</label>
                    <input type="number"
                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors"
                           wire:model="security.password_min_length"
                           min="6"
                           max="128">
                </div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox"
                           class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0"
                           id="pw_symbols"
                           wire:model="security.password_require_symbols">
                    <span class="text-sm text-slate-700">Require symbols</span>
                </label>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Expiration (days)</label>
                    <input type="number"
                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors"
                           wire:model="security.password_expiration_days"
                           min="0">
                    <small class="block mt-1.5 text-xs text-slate-500">0 = never expires</small>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div>
            <h5 class="text-base font-semibold text-slate-900 mb-4">Sessions & Limits</h5>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Session timeout (minutes)</label>
                    <input type="number"
                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors"
                           wire:model="security.session_timeout_minutes"
                           min="5"
                           max="10080">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Login attempt limit</label>
                    <input type="number"
                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors"
                           wire:model="security.login_max_attempts"
                           min="1"
                           max="100">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">API rate limit (per minute)</label>
                    <input type="number"
                           class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors"
                           wire:model="security.api_rate_limit_per_minute"
                           min="1"
                           max="10000">
                </div>
            </div>
        </div>

        <div>
            <h5 class="text-base font-semibold text-slate-900 mb-4">IP Access</h5>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Whitelist (comma separated)</label>
                    <textarea rows="2"
                              class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y"
                              wire:model="security.ip_whitelist"
                              placeholder="1.2.3.4, 10.0.0.0/8"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Blacklist (comma separated)</label>
                    <textarea rows="2"
                              class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y"
                              wire:model="security.ip_blacklist"
                              placeholder="5.6.7.8, 192.168.1.0/24"></textarea>
                </div>
            </div>
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

