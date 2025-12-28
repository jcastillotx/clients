<div class="space-y-4">
    <form wire:submit.prevent="save" class="relative">
        <!-- Submit overlay -->
        <div wire:loading.flex wire:target="save" class="absolute inset-0 z-10 items-center justify-center rounded-2xl bg-white/70 backdrop-blur-sm">
            <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-lg ring-1 ring-black/5">
                <svg class="h-5 w-5 animate-spin text-slate-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span class="text-sm font-semibold text-slate-700">Uploading…</span>
            </div>
        </div>

        <div class="space-y-4">
            @if($showClientSelector)
                <div>
                    <label class="text-xs font-semibold text-slate-600">Client <span class="text-rose-600">*</span></label>
                    <select wire:model.live="clientId" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                        <option value="">Select a client…</option>
                        @foreach($clients as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('clientId')
                        <div class="mt-1 flex items-start gap-2 text-xs font-medium text-rose-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>
            @endif

            <div>
                <label class="text-xs font-semibold text-slate-600">Title <span class="text-rose-600">*</span></label>
                <input wire:model.live.debounce.300ms="title" type="text" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="Document title" />
                @error('title')
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
                    Category <span class="text-rose-600">*</span>
                    <span class="ml-1 inline-flex items-center text-slate-400" title="Choose where this document belongs (contract, deliverable, report, etc.).">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </label>
                <select wire:model.live="category" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    @foreach($categories as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('category')
                    <div class="mt-1 flex items-start gap-2 text-xs font-medium text-rose-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <div
                x-data="{ isUploading: false, progress: 0 }"
                x-on:livewire-upload-start="isUploading = true"
                x-on:livewire-upload-finish="isUploading = false; progress = 0"
                x-on:livewire-upload-error="isUploading = false; progress = 0"
                x-on:livewire-upload-progress="progress = $event.detail.progress"
            >
                <label class="text-xs font-semibold text-slate-600">File <span class="text-rose-600">*</span></label>
                <input wire:model="file" type="file" class="mt-1 block w-full text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800" />
                <div class="mt-1 text-xs text-slate-500">Max 50MB · pdf/doc/docx/xls/xlsx/jpg/png/zip</div>
                @error('file')
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
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save,file">Upload</span>
                    <span wire:loading wire:target="save,file">Uploading…</span>
                </button>
                <button type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50" x-on:click="$dispatch('document-uploaded')">
                    Close
                </button>
            </div>
        </div>
    </form>
</div>

