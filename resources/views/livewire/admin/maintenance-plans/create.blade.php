<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-sm text-slate-500">Maintenance Plans</div>
            <div class="text-xl font-semibold text-slate-900">Create Maintenance Plan</div>
        </div>
        <a href="{{ route('admin.maintenance-plans.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
            Back
        </a>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
        <div>
            <label class="text-xs font-semibold text-slate-600">Client <span class="text-rose-600">*</span></label>
            <select wire:model="clientId" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                <option value="">Select a client</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                @endforeach
            </select>
            @error('clientId')
                <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-600">Plan Name <span class="text-rose-600">*</span></label>
            <input wire:model="name" type="text" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="e.g., Monthly Maintenance" />
            @error('name')
                <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-600">Description</label>
            <textarea wire:model="description" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="Description of what this plan covers"></textarea>
            @error('description')
                <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
            @enderror
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="text-xs font-semibold text-slate-600">Status <span class="text-rose-600">*</span></label>
                <select wire:model="status" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')
                    <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">Monthly Rate ($)</label>
                <input wire:model="monthlyRate" type="number" step="0.01" min="0" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="0.00" />
                @error('monthlyRate')
                    <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">Included Hours <span class="text-rose-600">*</span></label>
                <input wire:model="includedHours" type="number" min="0" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="0" />
                @error('includedHours')
                    <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">Overage Hourly Rate ($)</label>
                <input wire:model="hourlyRateOverage" type="number" step="0.01" min="0" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="0.00" />
                <div class="mt-1 text-xs text-slate-500">Rate for hours beyond included hours</div>
                @error('hourlyRateOverage')
                    <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">Start Date <span class="text-rose-600">*</span></label>
                <input wire:model="startDate" type="date" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" />
                @error('startDate')
                    <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">End Date</label>
                <input wire:model="endDate" type="date" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" />
                <div class="mt-1 text-xs text-slate-500">Leave blank for ongoing plans</div>
                @error('endDate')
                    <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="pt-4 flex items-center gap-3 border-t border-slate-200">
            <button wire:click="save" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                Create Plan
            </button>
            <a href="{{ route('admin.maintenance-plans.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-slate-700">
                Cancel
            </a>
        </div>
    </div>
</div>
