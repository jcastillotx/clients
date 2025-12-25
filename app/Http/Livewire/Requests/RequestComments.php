<?php

namespace App\Http\Livewire\Requests;

use App\Models\ActivityLog;
use App\Models\Request;
use App\Models\RequestComment;
use Livewire\Component;

class RequestComments extends Component
{
    public Request $request;

    public string $newComment = '';

    protected array $rules = [
        'newComment' => ['required', 'string', 'min:3', 'max:2000'],
    ];

    public function updated(string $property): void
    {
        $this->validateOnly($property);
    }

    public function mount(Request $request): void
    {
        $this->request = $request;
    }

    public function addComment(): void
    {
        $this->validate();

        $user = auth()->user();

        // Client users can only comment on their own request
        if ($user->isClient() && $this->request->client_id !== $user->client_id) {
            abort(403);
        }

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

        $this->newComment = '';
        $this->request->refresh();
    }

    public function render()
    {
        $user = auth()->user();

        $comments = $this->request->comments()
            ->when($user->isClient(), fn ($q) => $q->where('is_internal', false))
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('livewire.requests.comments', [
            'comments' => $comments,
        ]);
    }
}

