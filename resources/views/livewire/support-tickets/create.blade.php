<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-sm text-slate-500">Support</div>
            <div class="text-xl font-semibold text-slate-900">Create Support Ticket</div>
        </div>
        <a href="{{ route('support-tickets.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
            Back
        </a>
    </div>

    <!-- Error/Success Messages -->
    @if(session()->has('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-600 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <div class="text-sm text-rose-800">{{ session('error') }}</div>
            </div>
        </div>
    @endif

    <!-- Maintenance Plan Status -->
    @if($activePlan)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <div>
                    <div class="font-semibold text-emerald-800">Active Maintenance Plan: {{ $activePlan->name }}</div>
                    <div class="text-sm text-emerald-700">This ticket will be covered under your maintenance plan at no additional charge.</div>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <div>
                    <div class="font-semibold text-amber-800">No Active Maintenance Plan</div>
                    <div class="text-sm text-amber-700">This ticket will be billable. Time spent will be charged at ${{ number_format($estimatedHourlyRate, 2) }}/hour.</div>
                    <a href="#" class="mt-2 inline-block text-sm font-semibold text-amber-800 underline hover:no-underline">Learn about maintenance plans</a>
                </div>
            </div>
        </div>
    @endif

    <div class="form-card form-modern position-relative">
        <!-- Submit overlay -->
        <div wire:loading.flex wire:target="submit" class="absolute inset-0 z-10 items-center justify-center rounded-2xl bg-white/70 backdrop-blur-sm">
            <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-lg ring-1 ring-black/5">
                <svg class="h-5 w-5 animate-spin text-slate-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span class="text-sm font-semibold text-slate-700">Submitting...</span>
            </div>
        </div>

        <div>
            <label class="form-label-modern">Subject <span class="required">*</span></label>
            <input wire:model.live.debounce.300ms="subject" type="text" class="form-input" placeholder="Brief description of your issue" />
            @error('subject')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-grid-2">
            <div>
                <label class="form-label-modern">Category <span class="required">*</span></label>
                <select wire:model.live="category" class="form-select-modern">
                    @foreach($categories as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('category')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="form-label-modern">Priority</label>
                <select wire:model.live="priority" class="form-select-modern">
                    @foreach($priorities as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <div class="form-hint">Higher priority issues are addressed sooner.</div>
                @error('priority')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div>
            <label class="form-label-modern">Description <span class="required">*</span></label>
            <textarea wire:model.live.debounce.400ms="description" rows="6" class="form-textarea" placeholder="Please describe your issue in detail. Include steps to reproduce if applicable, any error messages, and what you expected to happen."></textarea>
            @error('description')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-actions border-top">
            <button type="button" wire:click="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 flex items-center gap-2" wire:loading.attr="disabled" wire:target="submit">
                <span wire:loading.remove wire:target="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                    </svg>
                    Submit Ticket
                </span>
                <span wire:loading wire:target="submit">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Submitting...
                </span>
            </button>

            <a href="{{ route('support-tickets.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-slate-700">
                Cancel
            </a>

            @if(!$activePlan)
                <div class="ml-auto text-xs text-amber-700 hidden sm:block">
                    <span class="font-medium">Note:</span> This ticket will be billable
                </div>
            @endif
        </div>
    </div>
</div>
