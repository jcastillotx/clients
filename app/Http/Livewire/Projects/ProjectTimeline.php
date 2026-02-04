<?php

namespace App\Http\Livewire\Projects;

use App\Models\Request as ServiceRequest;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProjectTimeline extends Component
{
    public ?int $requestId = null;

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);
    }

    public function updatedRequestId($value): void
    {
        $this->requestId = $value ? (int) $value : null;
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        $requests = ServiceRequest::query()->orderByDesc('id')->limit(200)->get(['id', 'title']);

        $tasks = collect();
        if ($this->requestId) {
            $tasks = Task::query()
                ->where('request_id', $this->requestId)
                ->orderBy('due_date')
                ->orderBy('status')
                ->get();
        }

        return view('livewire.projects.project-timeline', [
            'requests' => $requests,
            'tasks' => $tasks,
        ]);
    }
}
