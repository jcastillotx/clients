<div>
    <!-- Add Comment Form -->
    <form wire:submit="addComment" class="mb-4">
        <div class="form-group">
            <textarea wire:model="newComment" 
                      class="form-control @error('newComment') is-invalid @enderror" 
                      rows="3" 
                      placeholder="Add a comment..."></textarea>
            @error('newComment')
            <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
        <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="addComment">
                <i class="fas fa-comment mr-1"></i> Add Comment
            </span>
            <span wire:loading wire:target="addComment">
                <i class="fas fa-spinner fa-spin mr-1"></i> Posting...
            </span>
        </button>
    </form>

    <!-- Comments List -->
    <div class="comments-list">
        @forelse($comments as $comment)
        <div class="direct-chat-msg {{ $comment->user_id === auth()->id() ? 'right' : '' }} mb-3">
            <div class="direct-chat-infos clearfix">
                <span class="direct-chat-name {{ $comment->user_id === auth()->id() ? 'float-right' : 'float-left' }}">
                    {{ $comment->user?->name ?? 'Unknown' }}
                    @if($comment->is_internal)
                    <span class="badge badge-warning ml-1">Internal</span>
                    @endif
                </span>
                <span class="direct-chat-timestamp {{ $comment->user_id === auth()->id() ? 'float-left' : 'float-right' }}">
                    {{ $comment->created_at->diffForHumans() }}
                </span>
            </div>
            <div class="direct-chat-img">
                <div class="bg-{{ $comment->user_id === auth()->id() ? 'primary' : 'secondary' }} rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <span class="text-white font-weight-bold">{{ $comment->user?->initials ?? '?' }}</span>
                </div>
            </div>
            <div class="direct-chat-text">
                {!! nl2br(e($comment->comment)) !!}
            </div>
        </div>
        @empty
        <div class="text-center text-muted py-4">
            <i class="fas fa-comments fa-2x mb-2"></i>
            <p class="mb-0">No comments yet. Be the first to add one!</p>
        </div>
        @endforelse
    </div>
</div>
