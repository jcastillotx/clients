<div wire:poll.5s class="space-y-4">
    <form wire:submit.prevent="addComment" class="relative">
        <div wire:loading.flex wire:target="addComment" class="absolute inset-0 z-10 items-center justify-center rounded-2xl bg-white/70 backdrop-blur-sm">
            <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-lg ring-1 ring-black/5">
                <svg class="h-5 w-5 animate-spin text-slate-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span class="text-sm font-semibold text-slate-700">Posting…</span>
            </div>
        </div>

        <label class="text-xs font-semibold text-slate-600">Add a comment</label>
        <textarea
            wire:model.live.debounce.250ms="newComment"
            rows="3"
            class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900"
            placeholder="Write a comment…"
        ></textarea>
        @error('newComment')
            <div class="mt-1 flex items-start gap-2 text-xs font-medium text-rose-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                </svg>
                <span>{{ $message }}</span>
            </div>
        @enderror
        <div class="mt-2">
            <button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="addComment">Post comment</span>
                <span wire:loading wire:target="addComment">Posting…</span>
            </button>
        </div>
    </form>

    <div class="space-y-3">
        @forelse($comments as $comment)
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                <div class="flex items-center justify-between gap-3">
                    <div class="text-sm font-semibold text-slate-900">{{ $comment->user?->name ?? 'Unknown' }}</div>
                    <div class="text-xs text-slate-500">{{ $comment->created_at->diffForHumans() }}</div>
                </div>
                <div class="mt-2 whitespace-pre-wrap text-sm text-slate-700">{{ $comment->comment }}</div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">
                No comments yet.
            </div>
        @endforelse
    </div>
</div>

