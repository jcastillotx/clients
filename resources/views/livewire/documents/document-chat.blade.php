<div class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm text-slate-500">{{ $document ? 'Chat with document' : 'Chat across documents' }}</div>
            <div class="text-xl font-semibold text-slate-900">
                {{ $document ? ($document->title ?? $document->original_filename) : 'Document Q&A' }}
            </div>
        </div>
        @if($document)
            <a href="{{ route('documents.show', $document) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50">
                Back
            </a>
        @endif
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <div class="text-sm font-semibold text-slate-900">Conversation</div>
            <div class="mt-1 text-xs text-slate-500">Answers are grounded only in extracted document text.</div>
        </div>
        <div class="max-h-[55vh] overflow-auto px-5 py-4 space-y-3">
            @forelse($messages as $m)
                @if(($m['role'] ?? '') === 'user')
                    <div class="rounded-xl bg-slate-50 p-3">
                        <div class="text-xs font-semibold text-slate-500">You</div>
                        <div class="mt-1 text-sm text-slate-900 whitespace-pre-wrap">{{ $m['content'] ?? '' }}</div>
                    </div>
                @else
                    <div class="rounded-xl bg-white p-3 ring-1 ring-slate-200">
                        <div class="text-xs font-semibold text-slate-500">Assistant</div>
                        <div class="mt-1 text-sm text-slate-900 whitespace-pre-wrap">{{ $m['content'] ?? '' }}</div>
                    </div>
                @endif
            @empty
                <div class="text-sm text-slate-600">Ask a question to start.</div>
            @endforelse
        </div>
        <div class="border-t border-slate-200 px-5 py-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label class="text-xs font-semibold text-slate-600">Question</label>
                    <textarea wire:model="question" rows="2" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-900 focus:ring-1 focus:ring-slate-900" placeholder="Ask about clauses, dates, obligations, payment terms…"></textarea>
                </div>
                <button wire:click="send" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                    Send
                </button>
            </div>
            <div class="mt-2 text-xs text-slate-500">
                Tip: For cross-document Q&A, make sure embeddings have been generated for your documents.
            </div>
        </div>
    </div>
</div>

