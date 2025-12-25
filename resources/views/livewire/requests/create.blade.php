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

    <form wire:submit.prevent="save" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
        <div>
            <label class="text-xs font-semibold text-slate-600">Title <span class="text-rose-600">*</span></label>
            <input wire:model.defer="title" type="text" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900" />
            @error('title') <div class="mt-1 text-xs font-medium text-rose-700">{{ $message }}</div> @enderror
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <label class="text-xs font-semibold text-slate-600">Type <span class="text-rose-600">*</span></label>
                <select wire:model.defer="type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('type') <div class="mt-1 text-xs font-medium text-rose-700">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-600">Priority</label>
                <select wire:model.defer="priority" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900">
                    @foreach($priorities as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('priority') <div class="mt-1 text-xs font-medium text-rose-700">{{ $message }}</div> @enderror
            </div>
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-600">Description <span class="text-rose-600">*</span></label>
            <textarea wire:model.defer="description" rows="6" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-900 focus:ring-1 focus:ring-slate-900"></textarea>
            @error('description') <div class="mt-1 text-xs font-medium text-rose-700">{{ $message }}</div> @enderror
        </div>

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
            @error('files.*') <div class="mt-1 text-xs font-medium text-rose-700">{{ $message }}</div> @enderror

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
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save,files">Save draft</span>
                <span wire:loading wire:target="save,files">Saving…</span>
            </button>
            <a href="{{ route('requests.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
                Cancel
            </a>
        </div>
    </form>
</div>

