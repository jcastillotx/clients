<div wire:poll.5s class="space-y-4">
    <form wire:submit.prevent="addComment">
        <label class="text-xs font-semibold text-slate-600">Add a comment</label>
        <textarea
            wire:model.defer="newComment"
            rows="3"
            class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900"
            placeholder="Write a comment…"
        ></textarea>
        @error('newComment')
            <div class="mt-1 text-xs font-medium text-rose-700">{{ $message }}</div>
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

