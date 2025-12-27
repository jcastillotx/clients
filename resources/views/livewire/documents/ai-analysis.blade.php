<div class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-500">Document AI</div>
            <div class="text-xl font-semibold text-slate-900">{{ $document->title ?? $document->original_filename }}</div>
            <div class="mt-1 text-sm text-slate-600">
                {{ $document->original_filename }} · {{ $document->human_file_size }} · {{ $document->mime_type }}
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('documents.show', $document) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
                Back
            </a>
            <button wire:click="refreshLatest" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
                Refresh
            </button>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-3 sm:grid-cols-3">
            <div>
                <label class="text-xs font-semibold text-slate-600">Force type (optional)</label>
                <select wire:model.live="forceType" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900">
                    <option value="">Auto-detect</option>
                    <option value="contract">Contract</option>
                    <option value="invoice">Invoice</option>
                    <option value="technical">Technical</option>
                    <option value="unknown">Generic summary</option>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600">Language (summary)</label>
                <input wire:model.live="language" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900" placeholder="en, es, fr, de…">
            </div>
            <div class="flex items-end">
                <button wire:click="analyze" class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                    Analyze
                </button>
            </div>
        </div>

        @if($latestTask)
            <div class="mt-4 text-sm text-slate-600">
                Latest task: <span class="font-semibold text-slate-900">#{{ $latestTask->id }}</span> ·
                Status: <span class="font-semibold text-slate-900">{{ $latestTask->status }}</span>
                @if($latestTask->cost !== null)
                    · AI cost: <span class="font-semibold text-slate-900">${{ number_format((float)$latestTask->cost, 2) }}</span>
                @endif
            </div>
        @endif
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm font-semibold text-slate-900">Results</div>
        @if(!$analysis)
            <div class="mt-2 text-sm text-slate-600">No analysis yet.</div>
        @else
            <pre class="mt-3 overflow-auto rounded-xl bg-slate-50 p-4 text-xs text-slate-800">{{ json_encode($analysis, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
        @endif
    </div>
</div>

