<div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
            <h3 class="text-base font-semibold text-slate-900">New Service Request</h3>
        </div>
        <form wire:submit="save">
            <div class="p-6 space-y-4">
                <div>
                    <label for="title" class="block text-sm font-semibold text-slate-700 mb-1">Title <span class="text-rose-600">*</span></label>
                    <input type="text"
                           wire:model="title"
                           id="title"
                           class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 @error('title') border-rose-500 @enderror"
                           placeholder="Brief summary of your request">
                    @error('title')
                    <span class="text-sm text-rose-600 mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-semibold text-slate-700 mb-1">Description <span class="text-rose-600">*</span></label>
                    <textarea wire:model="description"
                              id="description"
                              rows="6"
                              class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 @error('description') border-rose-500 @enderror"
                              placeholder="Provide detailed information about your request..."></textarea>
                    @error('description')
                    <span class="text-sm text-rose-600 mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label for="type" class="block text-sm font-semibold text-slate-700 mb-1">Request Type</label>
                        <select wire:model="type" id="type" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 @error('type') border-rose-500 @enderror">
                            @foreach($types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')
                        <span class="text-sm text-rose-600 mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label for="priority" class="block text-sm font-semibold text-slate-700 mb-1">Priority</label>
                        <select wire:model="priority" id="priority" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 @error('priority') border-rose-500 @enderror">
                            @foreach($priorities as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('priority')
                        <span class="text-sm text-rose-600 mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label for="due_date" class="block text-sm font-semibold text-slate-700 mb-1">Preferred Due Date</label>
                        <input type="date"
                               wire:model="due_date"
                               id="due_date"
                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 @error('due_date') border-rose-500 @enderror"
                               min="{{ now()->addDay()->format('Y-m-d') }}">
                        @error('due_date')
                        <span class="text-sm text-rose-600 mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="attachments" class="block text-sm font-semibold text-slate-700 mb-1">Attachments</label>
                    <div class="relative">
                        <input type="file"
                               wire:model="attachments"
                               id="attachments"
                               class="block w-full rounded-lg border border-slate-300 text-sm text-slate-900 file:mr-4 file:rounded-l-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-900 hover:file:bg-slate-200 focus:outline-none focus:ring-1 focus:ring-slate-900 @error('attachments.*') border-rose-500 @enderror"
                               multiple>
                    </div>
                    @error('attachments.*')
                    <span class="text-sm text-rose-600 mt-1">{{ $message }}</span>
                    @enderror
                    <small class="text-xs text-slate-500 mt-1 block">
                        Max file size: {{ config('client-portal.max_upload_size') / 1024 }}MB.
                        Allowed types: {{ implode(', ', config('client-portal.allowed_file_types')) }}
                    </small>
                </div>

                @if(count($attachments) > 0)
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Selected Files:</label>
                    <ul class="space-y-2">
                        @foreach($attachments as $index => $attachment)
                        <li class="flex items-center justify-between rounded-lg border border-slate-200 px-4 py-3">
                            <span class="flex items-center text-sm text-slate-900">
                                <i class="fas fa-file mr-2 text-slate-400"></i>
                                {{ $attachment->getClientOriginalName() }}
                                <small class="ml-2 text-slate-500">
                                    ({{ number_format($attachment->getSize() / 1024, 2) }} KB)
                                </small>
                            </span>
                            <button type="button"
                                    wire:click="removeAttachment({{ $index }})"
                                    class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                                <i class="fas fa-times"></i>
                            </button>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-6 py-4">
                <a href="{{ route('requests.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">Cancel</a>
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">
                        <i class="fas fa-paper-plane mr-1"></i> Submit Request
                    </span>
                    <span wire:loading wire:target="save">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Submitting...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
