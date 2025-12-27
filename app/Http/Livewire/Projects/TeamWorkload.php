<?php

namespace App\Http\Livewire\Projects;

use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TeamWorkload extends Component
{
    public function render()
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->isStaff()), 403);

        $users = User::query()->orderBy('name')->get(['id', 'name']);

        $rows = [];
        $weekStart = now()->startOfWeek();
        foreach ($users as $u) {
            $taskCounts = Task::query()
                ->where('assigned_to', $u->id)
                ->selectRaw("sum(case when status='todo' then 1 else 0 end) as todo")
                ->selectRaw("sum(case when status='in_progress' then 1 else 0 end) as in_progress")
                ->selectRaw("sum(case when status='blocked' then 1 else 0 end) as blocked")
                ->selectRaw("sum(case when status='done' then 1 else 0 end) as done")
                ->first();

            $minutes = (int) TimeEntry::query()
                ->where('user_id', $u->id)
                ->whereBetween('started_at', [$weekStart, now()])
                ->sum('duration_minutes');

            $rows[] = [
                'user' => $u,
                'todo' => (int) ($taskCounts?->todo ?? 0),
                'in_progress' => (int) ($taskCounts?->in_progress ?? 0),
                'blocked' => (int) ($taskCounts?->blocked ?? 0),
                'done' => (int) ($taskCounts?->done ?? 0),
                'hours_this_week' => round($minutes / 60, 2),
            ];
        }

        return view('livewire.projects.team-workload', [
            'rows' => $rows,
        ])->layout('layouts.admin', ['title' => 'Team Workload']);
    }
}

