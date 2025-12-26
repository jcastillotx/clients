<div class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-500">Summarize document</div>
            <div class="text-xl font-semibold text-slate-900">{{ $document->title ?? $document->original_filename }}</div>
        </div>
        <a href="{{ route('documents.show', $document) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
            Back
        </a>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label class="text-xs font-semibold text-slate-600">Language</label>
                <input wire:model.live="language" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900" placeholder="en, es, fr…">
            </div>
            <button wire:click="summarize" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                Summarize
            </button>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm font-semibold text-slate-900">Output</div>
        @if(!$summary)
            <div class="mt-2 text-sm text-slate-600">No summary yet.</div>
        @else
            <pre class="mt-3 overflow-auto rounded-xl bg-slate-50 p-4 text-xs text-slate-800">{{ json_encode($summary, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
        @endif
    </div>
</div>

