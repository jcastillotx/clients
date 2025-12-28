<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Company Information -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
            <h3 class="text-base font-semibold text-slate-900">Company Information</h3>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Company Name</label>
                <input type="text" wire:model="general.company_name" placeholder="Your Company Name" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Address</label>
                <textarea rows="3" wire:model="general.address" placeholder="123 Business St, City, State 12345" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y"></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Phone</label>
                <input type="tel" wire:model="general.phone" placeholder="+1 (555) 123-4567" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Website</label>
                <input type="url" wire:model="general.website" placeholder="https://yourcompany.com" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
            </div>
        </div>
    </div>

    <!-- Preferences -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
            <h3 class="text-base font-semibold text-slate-900">Preferences</h3>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Timezone</label>
                    <input type="text" wire:model="general.timezone" placeholder="e.g. UTC, America/New_York" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Default Currency</label>
                    <input type="text" wire:model="general.currency" placeholder="e.g. USD" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Business Hours</label>
                <input type="text" wire:model="general.business_hours" placeholder="9:00 AM - 5:00 PM EST" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Date Format</label>
                    <input type="text" wire:model="general.date_format" placeholder="e.g. M d, Y" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Time Format</label>
                    <input type="text" wire:model="general.time_format" placeholder="e.g. h:i A" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Language</label>
                <input type="text" wire:model="general.language" placeholder="e.g. en" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors">
            </div>
        </div>
    </div>
</div>

<div class="mt-6">
    <button type="button" wire:click="saveGeneral" wire:loading.attr="disabled" class="btn-primary-modern">
        <span wire:loading.remove wire:target="saveGeneral">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z" />
            </svg>
            Save General Settings
        </span>
        <span wire:loading wire:target="saveGeneral">
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Saving...
        </span>
    </button>
</div>
