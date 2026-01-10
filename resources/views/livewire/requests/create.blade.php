<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-sm text-slate-500">Requests</div>
            <div class="text-xl font-semibold text-slate-900">Create request</div>
        </div>
        <a href="{{ route('requests.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
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

    <div class="relative rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
        <!-- Submit overlay -->
        <div wire:loading.flex wire:target="saveDraft,submit" class="absolute inset-0 z-10 items-center justify-center rounded-2xl bg-white/70 backdrop-blur-sm">
            <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-lg ring-1 ring-black/5">
                <svg class="h-5 w-5 animate-spin text-slate-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span class="text-sm font-semibold text-slate-700">Processing…</span>
            </div>
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-600">Title <span class="text-rose-600">*</span></label>
            <input wire:model.live.debounce.300ms="title" type="text" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" />
            @error('title')
                <div class="mt-1 flex items-start gap-2 text-xs font-medium text-rose-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v3a1 1 0 102 0v-3z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ $message }}</span>
                </div>
            @enderror
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <label class="text-xs font-semibold text-slate-600">
                    Type <span class="text-rose-600">*</span>
                    <span class="ml-1 inline-flex items-center text-slate-400" title="Request type helps us route your request to the right team and set expectations.">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </label>
                <select wire:model.live="type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <div class="mt-1 text-xs text-slate-500">Pick the category that best matches the work (support, design, web, SEO, etc.).</div>
                @error('type')
                    <div class="mt-1 flex items-start gap-2 text-xs font-medium text-rose-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">
                    Priority
                    <span class="ml-1 inline-flex items-center text-slate-400" title="Priority helps us order work: Low = nice-to-have, Medium = normal, High/Urgent = time-sensitive.">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </label>
                <select wire:model.live="priority" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    @foreach($priorities as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <div class="mt-1 text-xs text-slate-500">Higher priority may require additional details (deadline, business impact).</div>
                @error('priority')
                    <div class="mt-1 flex items-start gap-2 text-xs font-medium text-rose-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-600">Description <span class="text-rose-600">*</span></label>
            <textarea wire:model.live.debounce.400ms="description" rows="6" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="Include goals, deadlines, links, and any context that helps."></textarea>
            @error('description')
                <div class="mt-1 flex items-start gap-2 text-xs font-medium text-rose-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ $message }}</span>
                </div>
            @enderror
        </div>

        <!-- Estimate Display -->
        @if($this->showEstimates && $this->estimatedTime)
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                    <div class="flex-1">
                        <div class="text-sm font-semibold text-blue-900">Estimated Time & Cost</div>
                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <div class="text-xs font-medium text-blue-700 uppercase tracking-wider">Time Estimate</div>
                                <div class="mt-1 text-lg font-semibold text-blue-900">{{ $this->estimatedTime['label'] }}</div>
                            </div>
                            @if($this->estimatedCost)
                                <div>
                                    <div class="text-xs font-medium text-blue-700 uppercase tracking-wider">Cost Estimate</div>
                                    <div class="mt-1 text-lg font-semibold text-blue-900">{{ $this->estimatedCost['formatted'] }}</div>
                                    @if($priority === 'high' || $priority === 'urgent')
                                        <div class="text-xs text-blue-600 mt-0.5">Includes {{ $priority === 'urgent' ? '50%' : '25%' }} priority surcharge</div>
                                    @endif
                                </div>
                            @endif
                        </div>
                        @if($this->estimateDisclaimer)
                            <div class="mt-3 text-xs text-blue-700 border-t border-blue-200 pt-2">
                                {{ $this->estimateDisclaimer }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div
            x-data="{ isUploading: false, progress: 0 }"
            x-on:livewire-upload-start="isUploading = true"
            x-on:livewire-upload-finish="isUploading = false; progress = 0"
            x-on:livewire-upload-error="isUploading = false; progress = 0"
            x-on:livewire-upload-progress="progress = $event.detail.progress"
        >
            <label class="text-xs font-semibold text-slate-600">Attachments</label>
            <input wire:model="files" type="file" multiple class="mt-1 block w-full text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800" />
            <div class="mt-1 text-xs text-slate-500">PDF/DOC/DOCX/JPG/PNG · max 10MB each</div>
            @error('files.*')
                <div class="mt-1 flex items-start gap-2 text-xs font-medium text-rose-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ $message }}</span>
                </div>
            @enderror

            <div x-show="isUploading" class="mt-3">
                <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
                    <div class="h-2 rounded-full bg-slate-900" :style="`width: ${progress}%`"></div>
                </div>
                <div class="mt-1 text-xs text-slate-500">Uploading… <span x-text="progress"></span>%</div>
            </div>

            @if(!empty($files))
                <div class="mt-3 space-y-2">
                    @foreach($files as $index => $file)
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                            <div class="min-w-0 text-sm text-slate-700 truncate">{{ $file->getClientOriginalName() }}</div>
                            <button type="button" wire:click="removeFile({{ $index }})" class="text-xs font-semibold text-rose-700 hover:underline">Remove</button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="pt-4 flex flex-wrap items-center gap-3 border-t border-slate-200">
            <!-- Submit Request Button (Primary) -->
            <button type="button" wire:click="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 flex items-center gap-2" wire:loading.attr="disabled" wire:target="saveDraft,submit,files">
                <span wire:loading.remove wire:target="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                    </svg>
                    Submit Request
                </span>
                <span wire:loading wire:target="submit">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Submitting…
                </span>
            </button>
            
            <!-- Save Draft Button (Secondary) -->
            <button type="button" wire:click="saveDraft" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2" wire:loading.attr="disabled" wire:target="saveDraft,submit,files">
                <span wire:loading.remove wire:target="saveDraft">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z" />
                    </svg>
                    Save Draft
                </span>
                <span wire:loading wire:target="saveDraft">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Saving…
                </span>
            </button>
            
            <a href="{{ route('requests.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-slate-700">
                Cancel
            </a>
            
            <div class="ml-auto text-xs text-slate-500 hidden sm:block">
                <span class="font-medium">Tip:</span> Save as draft to continue editing later
            </div>
        </div>
    </div>
</div>

