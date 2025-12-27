<?php

namespace App\Http\Livewire\Client\Social;

use App\Models\ContentCalendarItem;
use Livewire\Component;
use Livewire\WithPagination;

class PendingApprovals extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $selectedPost = null;

    public $feedbackText = '';

    public $editedContent = '';

    public $showFeedbackModal = false;

    protected $rules = [
        'feedbackText' => 'required|string|min:10',
        'editedContent' => 'nullable|string',
    ];

    public function selectPost($postId)
    {
        $this->selectedPost = ContentCalendarItem::with(['creator', 'feedback.user'])
            ->findOrFail($postId);
        $this->editedContent = $this->selectedPost->content_text;
    }

    public function approvePost($postId)
    {
        $post = ContentCalendarItem::forClient(auth()->user()->client_id)
            ->findOrFail($postId);

        if (! $post->isPendingApproval()) {
            session()->flash('error', 'This post is not pending approval.');

            return;
        }

        $post->approve(auth()->id());

        // Add approval note
        $post->feedback()->create([
            'user_id' => auth()->id(),
            'feedback_text' => 'Post approved and ready for scheduling.',
            'feedback_type' => 'approval_note',
            'is_resolved' => true,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        // Notify staff who created the post
        if ($post->creator) {
            $post->creator->notify(new \App\Notifications\SocialPostApproved($post));
        }

        // Also notify all staff with admin panel access
        $staffUsers = \App\Models\User::permission('access admin panel')->get();
        foreach ($staffUsers as $user) {
            if ($user->id !== $post->creator?->id) {
                $user->notify(new \App\Notifications\SocialPostApproved($post));
            }
        }

        session()->flash('success', 'Post approved successfully! Staff will schedule it for publishing.');
        $this->selectedPost = null;
        $this->resetPage();
    }

    public function openFeedbackModal($postId)
    {
        $this->selectPost($postId);
        $this->feedbackText = '';
        $this->showFeedbackModal = true;
    }

    public function closeFeedbackModal()
    {
        $this->showFeedbackModal = false;
        $this->feedbackText = '';
        $this->selectedPost = null;
    }

    public function requestChanges()
    {
        $this->validate([
            'feedbackText' => 'required|string|min:10',
        ]);

        if (! $this->selectedPost || ! $this->selectedPost->isPendingApproval()) {
            session()->flash('error', 'This post is not pending approval.');

            return;
        }

        $this->selectedPost->requestChanges(auth()->id(), $this->feedbackText);

        // If client edited the content, store it in meta
        if ($this->editedContent !== $this->selectedPost->content_text) {
            $meta = $this->selectedPost->meta ?? [];
            $meta['client_suggested_edit'] = $this->editedContent;
            $this->selectedPost->update(['meta' => $meta]);
        }

        // Get the latest feedback (the one we just created)
        $latestFeedback = $this->selectedPost->feedback()->latest()->first();

        // Notify staff who created the post
        if ($this->selectedPost->creator && $latestFeedback) {
            $this->selectedPost->creator->notify(new \App\Notifications\SocialPostChangesRequested($this->selectedPost, $latestFeedback));
        }

        // Also notify all staff with admin panel access
        if ($latestFeedback) {
            $staffUsers = \App\Models\User::permission('access admin panel')->get();
            foreach ($staffUsers as $user) {
                if ($user->id !== $this->selectedPost->creator?->id) {
                    $user->notify(new \App\Notifications\SocialPostChangesRequested($this->selectedPost, $latestFeedback));
                }
            }
        }

        session()->flash('success', 'Changes requested. Staff will revise the post and resubmit for your approval.');
        $this->closeFeedbackModal();
        $this->resetPage();
    }

    public function addComment()
    {
        $this->validate([
            'feedbackText' => 'required|string|min:10',
        ]);

        if (! $this->selectedPost) {
            session()->flash('error', 'No post selected.');

            return;
        }

        $this->selectedPost->feedback()->create([
            'user_id' => auth()->id(),
            'feedback_text' => $this->feedbackText,
            'feedback_type' => 'comment',
        ]);

        session()->flash('success', 'Comment added successfully.');
        $this->feedbackText = '';
    }

    public function render()
    {
        $client = auth()->user()->client;

        $pendingPosts = ContentCalendarItem::with(['creator', 'feedback.user'])
            ->forClient($client->id)
            ->pendingApproval()
            ->latest()
            ->paginate(10);

        $stats = [
            'pending' => ContentCalendarItem::forClient($client->id)->pendingApproval()->count(),
            'approved' => ContentCalendarItem::forClient($client->id)->approved()->count(),
            'scheduled' => ContentCalendarItem::forClient($client->id)->scheduled()->count(),
            'published' => ContentCalendarItem::forClient($client->id)->published()->count(),
        ];

        return view('livewire.client.social.pending-approvals', [
            'pendingPosts' => $pendingPosts,
            'stats' => $stats,
        ]);
    }
}
