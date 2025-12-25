<div class="space-y-4">
    <form wire:submit.prevent="save">
        <div class="space-y-4">
            <div>
                <label class="text-xs font-semibold text-slate-600">Title <span class="text-rose-600">*</span></label>
                <input wire:model.defer="title" type="text" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="Document title" />
                @error('title') <div class="mt-1 text-xs font-medium text-rose-700">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">Category <span class="text-rose-600">*</span></label>
                <select wire:model.defer="category" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    @foreach($categories as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('category') <div class="mt-1 text-xs font-medium text-rose-700">{{ $message }}</div> @enderror
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
                @error('file') <div class="mt-1 text-xs font-medium text-rose-700">{{ $message }}</div> @enderror

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

