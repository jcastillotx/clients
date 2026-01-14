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

    <div class="form-card form-modern">
        <div>
            <label class="form-label-modern">Client <span class="required">*</span></label>
            <select wire:model="clientId" class="form-select-modern">
                <option value="">Select a client</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                @endforeach
            </select>
            @error('clientId')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="form-label-modern">Plan Name <span class="required">*</span></label>
            <input wire:model="name" type="text" class="form-input" placeholder="e.g., Monthly Maintenance" />
            @error('name')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="form-label-modern">Description</label>
            <textarea wire:model="description" rows="3" class="form-textarea" placeholder="Description of what this plan covers"></textarea>
            @error('description')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-grid-2">
            <div>
                <label class="form-label-modern">Status <span class="required">*</span></label>
                <select wire:model="status" class="form-select-modern">
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="form-label-modern">Monthly Rate ($)</label>
                <input wire:model="monthlyRate" type="number" step="0.01" min="0" class="form-input" placeholder="0.00" />
                @error('monthlyRate')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="form-label-modern">Included Hours <span class="required">*</span></label>
                <input wire:model="includedHours" type="number" min="0" class="form-input" placeholder="0" />
                @error('includedHours')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="form-label-modern">Overage Hourly Rate ($)</label>
                <input wire:model="hourlyRateOverage" type="number" step="0.01" min="0" class="form-input" placeholder="0.00" />
                <div class="form-hint">Rate for hours beyond included hours</div>
                @error('hourlyRateOverage')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="form-label-modern">Start Date <span class="required">*</span></label>
                <input wire:model="startDate" type="date" class="form-input" />
                @error('startDate')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="form-label-modern">End Date</label>
                <input wire:model="endDate" type="date" class="form-input" />
                <div class="form-hint">Leave blank for ongoing plans</div>
                @error('endDate')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-actions border-top">
            <button wire:click="save" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                Create Plan
            </button>
            <a href="{{ route('admin.maintenance-plans.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-slate-700">
                Cancel
            </a>
        </div>
    </div>
</div>
