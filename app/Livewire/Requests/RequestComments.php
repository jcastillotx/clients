<?php

namespace App\Livewire\Requests;

use App\Models\Request;
use App\Models\RequestComment;
use App\Models\ActivityLog;
use Livewire\Component;

class RequestComments extends Component
{
    public Request $request;
    public string $newComment = '';

    protected $rules = [
        'newComment' => 'required|string|min:5|max:2000',
    ];

    protected $messages = [
        'newComment.required' => 'Please enter a comment.',
        'newComment.min' => 'Your comment must be at least 5 characters.',
    ];

    public function mount(Request $request): void
    {
        $this->request = $request;
    }

    public function addComment(): void
    {
        $this->validate();

        $user = auth()->user();

        RequestComment::create([
            'request_id' => $this->request->id,
            'user_id' => $user->id,
            'comment' => $this->newComment,
            'is_internal' => false,
        ]);

        ActivityLog::log(
            "Added comment to request: {$this->request->title}",
            $this->request,
            null,
            'commented',
            'requests'
        );

        $this->reset('newComment');
        $this->request->refresh();

        $this->dispatch('comment-added');
    }

    public function render()
    {
        $user = auth()->user();

        // Clients only see public comments
        $comments = $this->request->comments()
            ->when($user->isClient(), function ($q) {
                $q->where('is_internal', false);
            })
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.requests.request-comments', [
            'comments' => $comments,
        ]);
    }
}
