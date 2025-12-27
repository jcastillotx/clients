<?php

namespace App\Http\Livewire\Projects;

use App\Models\Request as ServiceRequest;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Services\Marketing\TimeTrackingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class TimeTracker extends Component
{
    public ?int $requestId = null;
    public ?int $taskId = null;
    public string $description = '';
    public bool $isBillable = true;

    // Manual entry
    public string $manualDate = '';
    public int $manualMinutes = 30;

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);
        $this->manualDate = now()->toDateString();
    }

    public function start(TimeTrackingService $svc): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        Validator::make(['requestId' => $this->requestId], [
            'requestId' => ['required', 'integer', 'exists:requests,id'],
        ])->validate();

        $req = ServiceRequest::query()->findOrFail($this->requestId);
        $task = $this->taskId ? Task::query()->where('request_id', $req->id)->find($this->taskId) : null;

        $svc->startTimer($user, $req, $task, [
            'description' => trim($this->description) ?: null,
            'is_billable' => (bool) $this->isBillable,
        ]);

        session()->flash('success', 'Timer started.');
    }

    public function stop(TimeTrackingService $svc): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        $running = TimeEntry::query()
            ->where('user_id', $user->id)
            ->whereNull('ended_at')
            ->orderByDesc('started_at')
            ->first();

        if ($running) {
            $svc->stopTimer($running);
        }

        session()->flash('success', 'Timer stopped.');
    }

    public function addManual(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        Validator::make([
            'requestId' => $this->requestId,
            'manualDate' => $this->manualDate,
            'manualMinutes' => $this->manualMinutes,
        ], [
            'requestId' => ['required', 'integer', 'exists:requests,id'],
            'manualDate' => ['required', 'date'],
            'manualMinutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ])->validate();

        $start = now()->parse($this->manualDate)->setTime(9, 0);
        $end = (clone $start)->addMinutes((int) $this->manualMinutes);

        TimeEntry::create([
            'user_id' => $user->id,
            'request_id' => (int) $this->requestId,
            'task_id' => $this->taskId ?: null,
            'started_at' => $start,
            'ended_at' => $end,
            'duration_minutes' => (int) $this->manualMinutes,
            'description' => trim($this->description) ?: null,
            'is_billable' => (bool) $this->isBillable,
            'status' => 'pending',
        ]);

        session()->flash('success', 'Manual time entry added.');
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        $requests = ServiceRequest::query()->orderByDesc('id')->limit(200)->get(['id', 'title', 'client_id']);
        $tasks = [];
        if ($this->requestId) {
            $tasks = Task::query()->where('request_id', $this->requestId)->orderBy('order')->get(['id', 'title']);
        }

        $running = TimeEntry::query()->where('user_id', $user->id)->whereNull('ended_at')->orderByDesc('started_at')->first();
        $recent = TimeEntry::query()->where('user_id', $user->id)->orderByDesc('started_at')->limit(15)->get();

        return view('livewire.projects.time-tracker', [
            'requests' => $requests,
            'tasks' => $tasks,
            'running' => $running,
            'recent' => $recent,
        ])->layout('layouts.admin', ['title' => 'Time Tracker']);
    }
}

