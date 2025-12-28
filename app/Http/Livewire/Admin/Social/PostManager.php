<?php

namespace App\Http\Livewire\Admin\Social;

use App\Models\Client;
use App\Models\ContentCalendarItem;
use Livewire\Component;
use Livewire\WithPagination;

class PostManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $selectedClient = '';

    public $selectedPlatform = '';

    public $selectedStatus = '';

    public $search = '';

    public $dateFrom = '';

    public $dateTo = '';

    protected $queryString = [
        'selectedClient' => ['except' => ''],
        'selectedPlatform' => ['except' => ''],
        'selectedStatus' => ['except' => ''],
        'search' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedClient()
    {
        $this->resetPage();
    }

    public function updatingSelectedPlatform()
    {
        $this->resetPage();
    }

    public function updatingSelectedStatus()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->selectedClient = '';
        $this->selectedPlatform = '';
        $this->selectedStatus = '';
        $this->search = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function deletePost($postId)
    {
        $post = ContentCalendarItem::findOrFail($postId);

        // Only allow deleting drafts and failed posts
        if (in_array($post->status, ['draft', 'failed'])) {
            $post->delete();
            session()->flash('success', 'Post deleted successfully.');
        } else {
            session()->flash('error', 'Cannot delete posts that are pending approval, approved, or published.');
        }
    }

    public function duplicatePost($postId)
    {
        $post = ContentCalendarItem::findOrFail($postId);

        $newPost = $post->replicate();
        $newPost->title .= ' (Copy)';
        $newPost->status = 'draft';
        $newPost->approved_by = null;
        $newPost->published_at = null;
        $newPost->scheduled_for = null;
        $newPost->created_by = auth()->id();
        $newPost->save();

        session()->flash('success', 'Post duplicated successfully.');

        return redirect()->route('admin.social.posts.edit', $newPost->id);
    }

    public function render()
    {
        $query = ContentCalendarItem::with(['client', 'creator', 'approver'])
            ->where('content_type', 'social');

        // Apply filters
        if ($this->selectedClient) {
            $query->where('client_id', $this->selectedClient);
        }

        if ($this->selectedPlatform) {
            $query->where('platform', $this->selectedPlatform);
        }

        if ($this->selectedStatus) {
            $query->where('status', $this->selectedStatus);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('content_text', 'like', "%{$this->search}%")
                    ->orWhere('hashtags', 'like', "%{$this->search}%");
            });
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $posts = $query->latest()->paginate(20);

        // Stats
        $stats = [
            'total' => ContentCalendarItem::where('content_type', 'social')->count(),
            'draft' => ContentCalendarItem::where('content_type', 'social')->draft()->count(),
            'pending_approval' => ContentCalendarItem::where('content_type', 'social')->pendingApproval()->count(),
            'approved' => ContentCalendarItem::where('content_type', 'social')->approved()->count(),
            'scheduled' => ContentCalendarItem::where('content_type', 'social')->scheduled()->count(),
            'published' => ContentCalendarItem::where('content_type', 'social')->published()->count(),
        ];

        return view('livewire.admin.social.post-manager', [
            'posts' => $posts,
            'clients' => Client::active()->orderBy('company_name')->get(),
            'platforms' => ['facebook', 'instagram', 'linkedin', 'x', 'tiktok', 'pinterest'],
            'statuses' => [
                'draft' => 'Draft',
                'pending_approval' => 'Pending Approval',
                'approved' => 'Approved',
                'needs_revision' => 'Needs Revision',
                'scheduled' => 'Scheduled',
                'published' => 'Published',
                'failed' => 'Failed',
            ],
            'stats' => $stats,
        ])->layout('layouts.admin');
    }
}
