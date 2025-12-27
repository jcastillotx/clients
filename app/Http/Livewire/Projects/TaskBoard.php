<?php

namespace App\Http\Livewire\Projects;

use App\Models\Request as ServiceRequest;
use App\Models\Task;
use App\Models\RequestEstimate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class TaskBoard extends Component
{
    public ?int $requestId = null;
    public string $newTitle = '';
    public string $newDescription = '';

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);
    }

    public function addTask(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        Validator::make([
            'requestId' => $this->requestId,
            'newTitle' => $this->newTitle,
        ], [
            'requestId' => ['required', 'integer', 'exists:requests,id'],
            'newTitle' => ['required', 'string', 'max:255'],
        ])->validate();

        Task::create([
            'request_id' => (int) $this->requestId,
            'title' => trim($this->newTitle),
            'description' => trim($this->newDescription) ?: null,
            'status' => 'todo',
            'priority' => 'normal',
            'order' => 0,
        ]);

        $this->newTitle = '';
        $this->newDescription = '';
        session()->flash('success', 'Task added.');
    }

    public function seedFromEstimate(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);
        abort_unless($this->requestId, 422);

        $estimate = RequestEstimate::query()
            ->where('request_id', $this->requestId)
            ->orderByDesc('id')
            ->first();

        $tasks = (array) ($estimate?->estimate_data['tasks'] ?? []);
        $order = 0;
        foreach ($tasks as $t) {
            if (!is_array($t)) continue;
            $name = trim((string) ($t['name'] ?? ''));
            if ($name === '') continue;

            Task::firstOrCreate(
                [
                    'request_id' => (int) $this->requestId,
                    'title' => $name,
                ],
                [
                    'description' => (string) ($t['description'] ?? '') ?: null,
                    'status' => 'todo',
                    'priority' => 'normal',
                    'estimated_hours' => (float) (($t['hours_mid'] ?? null) ?: null),
                    'order' => $order++,
                ]
            );
        }

        session()->flash('success', 'Seeded tasks from latest estimate (where available).');
    }

    public function moveTask(int $taskId, string $status): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        abort_unless(in_array($status, ['todo', 'in_progress', 'blocked', 'done'], true), 422);
        $task = Task::query()->findOrFail($taskId);
        $task->update(['status' => $status]);
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        $requests = ServiceRequest::query()->orderByDesc('id')->limit(200)->get(['id', 'title']);

        $board = [
            'todo' => [],
            'in_progress' => [],
            'blocked' => [],
            'done' => [],
        ];

        if ($this->requestId) {
            $tasks = Task::query()
                ->where('request_id', $this->requestId)
                ->orderBy('order')
                ->orderBy('id')
                ->get();

            foreach ($tasks as $t) {
                $board[$t->status] ??= [];
                $board[$t->status][] = $t;
            }
        }

        return view('livewire.projects.task-board', [
            'requests' => $requests,
            'board' => $board,
        ])->layout('layouts.admin', ['title' => 'Task Board']);
    }
}

