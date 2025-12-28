<div class="max-w-5xl mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-slate-900">Storage Settings</h2>
        <p class="text-sm text-slate-500 mt-1">Configure sync, backup, and conflict resolution settings</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-600" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
            </svg>
            <h3 class="text-base font-semibold text-slate-900">Configuration</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Sync Settings -->
                <div class="space-y-4">
                    <h4 class="text-sm font-semibold text-slate-900 mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                        </svg>
                        Sync Settings
                    </h4>
                    
                    <div class="pt-2">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model.defer="auto_sync_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                            <span class="text-sm text-slate-700">Auto-sync enabled</span>
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Auto-sync Frequency</label>
                        <select wire:model.defer="auto_sync_frequency" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            <option value="hourly">Hourly</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Quota Alert Threshold (%)</label>
                        <input type="number" wire:model.defer="quota_alert_percent" min="1" max="100" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        <p class="mt-1.5 text-xs text-slate-500">Clients are notified when a provider reaches this threshold.</p>
                    </div>
                </div>

                <!-- Primary & Conflict Settings -->
                <div class="space-y-4">
                    <h4 class="text-sm font-semibold text-slate-900 mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                        </svg>
                        Provider Settings
                    </h4>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Primary Storage Provider</label>
                        <select wire:model.defer="primary_connection_id" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            <option value="">(none)</option>
                            @foreach($connections as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ strtoupper($c->provider) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Conflict Resolution</label>
                        <select wire:model.defer="conflict_rule" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                            <option value="prefer_primary">Prefer primary</option>
                            <option value="prefer_newest">Prefer newest</option>
                            <option value="keep_both">Keep both (log conflicts)</option>
                        </select>
                        <p class="mt-1.5 text-xs text-slate-500">Conflicts are detected when the same filename differs across providers.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Folders to Sync</label>
                        <input type="text" wire:model.defer="folders_csv" placeholder="e.g. ., invoices, contracts" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                        <p class="mt-1.5 text-xs text-slate-500">Comma-separated folder paths.</p>
                    </div>
                </div>

                <!-- Backup Settings -->
                <div class="space-y-4">
                    <h4 class="text-sm font-semibold text-slate-900 mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V8z" clip-rule="evenodd" />
                        </svg>
                        Backup Settings
                    </h4>

                    <div class="pt-2">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model.defer="backup_enabled" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 focus:ring-offset-0">
                            <span class="text-sm text-slate-700">Backup enabled</span>
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Backup Destination</label>
                        <select wire:model.defer="backup_connection_id" @disabled(!$backup_enabled) class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 bg-white focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors disabled:bg-slate-50 disabled:text-slate-500">
                            <option value="">Select provider...</option>
                            @foreach($connections as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-slate-500">Configure your provider disks first before enabling backup.</p>
                    </div>
                </div>
            </div>

            <div class="pt-6 mt-6 border-t border-slate-200">
                <button type="button" wire:click="save" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z" />
                    </svg>
                    Save Settings
                </button>
            </div>
        </div>
    </div>
</div>
