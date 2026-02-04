<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-500">Request #{{ $request->id }}</div>
            <div class="text-xl font-semibold text-slate-900">Edit request</div>
        </div>
        <div class="flex gap-2">
            <x-button 
                variant="outline" 
                size="sm" 
                href="{{ route('requests.show', $request) }}"
                icon="chevron-left"
            >
                Back
            </x-button>
        </div>
    </div>

    <form wire:submit.prevent="save" class="relative rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
        <!-- Submit overlay -->
        <div wire:loading.flex wire:target="save" class="absolute inset-0 z-10 items-center justify-center rounded-2xl bg-white/70 backdrop-blur-sm">
            <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-lg ring-1 ring-black/5">
                <svg class="h-5 w-5 animate-spin text-slate-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span class="text-sm font-semibold text-slate-700">Saving…</span>
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
                <div class="mt-1 text-xs text-slate-500">If you change the type, it may be routed to a different team.</div>
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
            <textarea wire:model.live.debounce.400ms="description" rows="6" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900"></textarea>
            @error('description')
                <div class="mt-1 flex items-start gap-2 text-xs font-medium text-rose-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ $message }}</span>
                </div>
            @enderror
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-900">Existing attachments</div>
                <div class="text-xs text-slate-500">{{ $request->attachments->count() }}</div>
            </div>
            <div class="mt-3 space-y-2">
                @forelse($request->attachments as $attachment)
                    <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2">
                        <a href="{{ $attachment->url }}" class="min-w-0 truncate text-sm font-semibold text-slate-900 hover:underline">
                            {{ $attachment->original_filename }}
                        </a>
                        <button
                            type="button"
                            wire:click="removeExistingAttachment({{ $attachment->id }})"
                            onclick="confirm('Remove this attachment?') || event.stopImmediatePropagation()"
                            class="text-xs font-semibold text-rose-700 hover:underline"
                        >
                            Remove
                        </button>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">
                        No attachments.
                    </div>
                @endforelse
            </div>
        </div>

        <div
            x-data="{ isUploading: false, progress: 0 }"
            x-on:livewire-upload-start="isUploading = true"
            x-on:livewire-upload-finish="isUploading = false; progress = 0"
            x-on:livewire-upload-error="isUploading = false; progress = 0"
            x-on:livewire-upload-progress="progress = $event.detail.progress"
        >
            <label class="text-xs font-semibold text-slate-600">Add new attachments</label>
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

        <div class="pt-2 flex items-center gap-2">
            <x-button 
                type="submit" 
                variant="primary" 
                size="md"
                wire:loading.attr="disabled"
                wire-target="save,files"
                loading-text="Saving…"
                icon="check"
            >
                Save changes
            </x-button>
            
            <x-button 
                variant="outline" 
                size="md"
                href="{{ route('requests.show', $request) }}"
            >
                Cancel
            </x-button>
        </div>
    </form>
</div>

