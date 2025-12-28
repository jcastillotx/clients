<div class="container-fluid">
    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pending Your Approval</span>
                    <span class="info-box-number">{{ $stats['pending'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Approved</span>
                    <span class="info-box-number">{{ $stats['approved'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-primary"><i class="fas fa-calendar"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Scheduled</span>
                    <span class="info-box-number">{{ $stats['scheduled'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-info"><i class="fas fa-paper-plane"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Published</span>
                    <span class="info-box-number">{{ $stats['published'] }}</span>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-2"></i>
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <!-- Pending Posts -->
    <div class="row">
        @forelse($pendingPosts as $post)
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0">
                            <i class="{{ $post->platform_icon }} mr-2"></i>
                            {{ ucfirst($post->platform) }} Post
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6 class="font-weight-bold mb-3">{{ $post->title }}</h6>

                        <!-- Post Content -->
                        <div class="post-content mb-3 p-3 bg-light rounded">
                            <div style="white-space: pre-wrap;">{{ $post->content_text }}</div>

                            @if($post->hashtags)
                                <div class="mt-3">
                                    @foreach(explode(' ', $post->hashtags) as $hashtag)
                                        @if(trim($hashtag))
                                            <span class="badge badge-secondary">{{ $hashtag }}</span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Post Details -->
                        <div class="small text-muted mb-3">
                            <div><strong>Created by:</strong> {{ $post->creator->name ?? 'Staff' }}</div>
                            <div><strong>Created:</strong> {{ $post->created_at->format('M d, Y g:i A') }}</div>
                            @if($post->campaign_tag)
                                <div><strong>Campaign:</strong> <span class="badge badge-info">{{ $post->campaign_tag }}</span></div>
                            @endif
                            @if($post->scheduled_for)
                                <div><strong>Scheduled for:</strong> {{ $post->scheduled_for->format('M d, Y g:i A') }}</div>
                            @endif
                        </div>

                        <!-- Previous Feedback -->
                        @if($post->feedback->count() > 0)
                            <div class="mb-3">
                                <strong>Previous Comments:</strong>
                                @foreach($post->feedback as $feedback)
                                    <div class="alert alert-info small mb-2">
                                        <strong>{{ $feedback->user->name }}:</strong> {{ $feedback->feedback_text }}
                                        <br><small class="text-muted">{{ $feedback->created_at->diffForHumans() }}</small>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Action Buttons -->
                        <div class="btn-group btn-group-sm d-flex">
                            <button wire:click="approvePost({{ $post->id }})"
                                    onclick="return confirm('Are you sure you want to approve this post?')"
                                    class="btn btn-success flex-fill">
                                <i class="fas fa-check mr-1"></i>
                                Approve
                            </button>
                            <button wire:click="openFeedbackModal({{ $post->id }})"
                                    class="btn btn-warning flex-fill">
                                <i class="fas fa-edit mr-1"></i>
                                Request Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <h4>All Caught Up!</h4>
                        <p class="text-muted">You have no posts pending approval at this time.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($pendingPosts->hasPages())
        <div class="mt-3">
            {{ $pendingPosts->links() }}
        </div>
    @endif

    <!-- Request Changes Modal -->
    @if($showFeedbackModal && $selectedPost)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">Request Changes</h5>
                        <button wire:click="closeFeedbackModal" type="button" class="close">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="font-weight-bold">Current Post Content:</label>
                            <div class="p-3 bg-light rounded">
                                <div style="white-space: pre-wrap;">{{ $selectedPost->content_text }}</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Edit Content (Optional):</label>
                            <p class="small text-muted">You can suggest edits to the content. Staff will review your changes.</p>
                            <textarea wire:model="editedContent" class="form-control" rows="6"></textarea>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Your Feedback: <span class="text-danger">*</span></label>
                            <p class="small text-muted">Please explain what changes you'd like to see.</p>
                            <textarea wire:model="feedbackText"
                                      class="form-control @error('feedbackText') is-invalid @enderror"
                                      rows="4"
                                      placeholder="E.g., Please make the tone more casual, add a call-to-action, etc."></textarea>
                            @error('feedbackText') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button wire:click="closeFeedbackModal" type="button" class="btn btn-secondary">Cancel</button>
                        <button wire:click="requestChanges" type="button" class="btn btn-warning">
                            <i class="fas fa-paper-plane mr-1"></i>
                            Submit Feedback
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // Close modal on escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            @this.call('closeFeedbackModal');
        }
    });
</script>
@endpush
