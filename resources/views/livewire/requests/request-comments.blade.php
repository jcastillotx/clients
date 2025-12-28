<div class="space-y-5">
    <!-- Add Comment Form -->
    <form wire:submit="addComment" class="relative">
        <div wire:loading.flex wire:target="addComment" class="absolute inset-0 z-10 items-center justify-center rounded-xl bg-white/70 backdrop-blur-sm">
            <div class="flex items-center gap-2 text-sm font-medium text-slate-700">
                <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                Posting…
            </div>
        </div>

        <div class="space-y-3">
            <textarea wire:model="newComment" 
                      rows="3" 
                      placeholder="Add a comment..."
                      class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:ring-1 focus:ring-slate-900 focus:outline-none transition-colors resize-y @error('newComment') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"></textarea>
            @error('newComment')
                <div class="flex items-start gap-1.5 text-xs font-medium text-rose-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm1 3a1 1 0 10-2 0v4a1 1 0 102 0V10z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ $message }}</span>
                </div>
            @enderror
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition-colors" wire:loading.attr="disabled">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 13V5a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2h3l3 3 3-3h3a2 2 0 002-2zM5 7a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm1 3a1 1 0 100 2h3a1 1 0 100-2H6z" clip-rule="evenodd" />
                </svg>
                <span wire:loading.remove wire:target="addComment">Add Comment</span>
                <span wire:loading wire:target="addComment">Posting…</span>
            </button>
        </div>
    </form>

    <!-- Comments List -->
    <div class="space-y-4">
        @forelse($comments as $comment)
            <div class="flex gap-3 {{ $comment->user_id === auth()->id() ? 'flex-row-reverse' : '' }}">
                <!-- Avatar -->
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full {{ $comment->user_id === auth()->id() ? 'bg-slate-900' : 'bg-slate-400' }} flex items-center justify-center">
                        <span class="text-white font-semibold text-sm">{{ $comment->user?->initials ?? '?' }}</span>
                    </div>
                </div>

                <!-- Comment Content -->
                <div class="flex-1 max-w-[85%]">
                    <div class="flex items-center gap-2 mb-1 {{ $comment->user_id === auth()->id() ? 'flex-row-reverse' : '' }}">
                        <span class="text-sm font-semibold text-slate-900">{{ $comment->user?->name ?? 'Unknown' }}</span>
                        @if($comment->is_internal)
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Internal</span>
                        @endif
                        <span class="text-xs text-slate-500">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="rounded-xl px-4 py-3 {{ $comment->user_id === auth()->id() ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-900' }}">
                        <p class="text-sm whitespace-pre-wrap">{!! nl2br(e($comment->comment)) !!}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-12">
                <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <p class="text-sm text-slate-500">No comments yet. Be the first to add one!</p>
            </div>
        @endforelse
    </div>
</div>
