<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Defaults & Slack/Teams -->
    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-base font-semibold text-slate-900">Defaults</h3>
            </div>
            <div class="p-6 space-y-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" wire:model.defer="notifications.admin_email" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                    <span class="text-sm text-slate-700">Admin email notifications enabled</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" wire:model.defer="notifications.client_email_default" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                    <span class="text-sm text-slate-700">Client email notifications default</span>
                </label>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-base font-semibold text-slate-900">Slack / Teams</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Slack Webhook URL</label>
                    <input type="url" wire:model.defer="notifications.slack_webhook_url" placeholder="https://hooks.slack.com/..." class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Teams Webhook URL</label>
                    <input type="url" wire:model.defer="notifications.teams_webhook_url" placeholder="https://outlook.office.com/webhook/..." class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <p class="text-xs text-slate-500">Integration execution is stored here; event wiring can be added per module.</p>
            </div>
        </div>
    </div>

    <!-- Push / SMS -->
    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-base font-semibold text-slate-900">Push / SMS</h3>
            </div>
            <div class="p-6 space-y-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" wire:model.defer="notifications.push_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                    <span class="text-sm text-slate-700">Push notifications enabled</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" wire:model.defer="notifications.sms_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                    <span class="text-sm text-slate-700">SMS alerts enabled</span>
                </label>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-base font-semibold text-slate-900">Twilio (SMS)</h3>
                <p class="text-sm text-slate-500 mt-0.5">Optional - Configure for SMS notifications</p>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Account SID</label>
                    <input type="text" wire:model.defer="notifications.twilio_sid" placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 font-mono placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Auth Token</label>
                    <input type="password" wire:model.defer="notifications.twilio_token" placeholder="••••••••••••••••" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">From Number</label>
                    <input type="tel" wire:model.defer="notifications.twilio_from" placeholder="+15551234567" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-6">
    <button type="button" wire:click="saveNotifications" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
            <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z" />
        </svg>
        Save Notification Settings
    </button>
</div>
