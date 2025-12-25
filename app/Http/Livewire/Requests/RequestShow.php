<?php

namespace App\Http\Livewire\Requests;

use App\Models\ActivityLog;
use App\Models\Request as ServiceRequest;
use App\Models\RequestComment;
use Illuminate\Support\Collection;
use Livewire\Component;

class RequestShow extends Component
{
    public ServiceRequest $request;

    public string $newComment = '';

    /** @var \Illuminate\Support\Collection<int, \App\Models\RequestComment> */
    public Collection $comments;

    /** @var \Illuminate\Support\Collection<int, \App\Models\ActivityLog> */
    public Collection $statusHistory;

    protected array $rules = [
        'newComment' => ['required', 'string', 'min:3', 'max:2000'],
    ];

    public function mount(ServiceRequest $request): void
    {
        $this->authorizeClientAccess($request);

        $this->request = $request->load([
            'client',
            'creator',
            'assignee',
            'attachments.uploader',
        ]);

        $this->loadComments();
        $this->loadStatusHistory();
    }

    public function addComment(): void
    {
        $this->validate();

        $user = auth()->user();

        // Clients can add public comments; staff/admin can also add (public by default here).
        RequestComment::create([
            'request_id' => $this->request->id,
            'user_id' => $user->id,
            'comment' => $this->newComment,
            'is_internal' => false,
        ]);

        $this->newComment = '';
        $this->request->refresh();
        $this->loadComments();
        $this->loadStatusHistory();

        session()->flash('success', 'Comment posted.');
    }

    protected function loadComments(): void
    {
        $user = auth()->user();

        $this->comments = $this->request->comments()
            ->when($user->isClient(), fn ($q) => $q->where('is_internal', false))
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    protected function loadStatusHistory(): void
    {
        $this->statusHistory = ActivityLog::query()
            ->where('subject_type', ServiceRequest::class)
            ->where('subject_id', $this->request->id)
            ->whereNotNull('event')
            ->latest()
            ->take(20)
            ->get();
    }

    protected function authorizeClientAccess(ServiceRequest $request): void
    {
        $user = auth()->user();

        if ($user->isClient() && $request->client_id !== $user->client_id) {
            abort(403);
        }
    }

    public function render()
    {
        return view('livewire.requests.show');
    }
}

