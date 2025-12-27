<?php

namespace App\Http\Livewire\Projects;

use App\Models\TimeEntry;
use App\Models\TimeEntryLock;
use App\Models\User;
use App\Services\Projects\TimeEntryLockService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TimeApprovals extends Component
{
    public ?int $userId = null;

    public string $weekStart = ''; // Y-m-d

    public function mount(TimeEntryLockService $locks): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        $this->weekStart = $locks->weekStart(now())->toDateString();
    }

    public function approve(int $entryId): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        $e = TimeEntry::query()->findOrFail($entryId);
        $e->update([
            'status' => 'approved',
            'approved_by' => $u->id,
            'approved_at' => now(),
        ]);
    }

    public function markBilled(int $entryId): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        $e = TimeEntry::query()->findOrFail($entryId);
        $e->update([
            'status' => 'billed',
            'billed_at' => now(),
        ]);
    }

    public function lockWeek(TimeEntryLockService $locks): void
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        abort_unless($this->userId, 422);
        abort_unless($this->weekStart !== '', 422);

        $target = User::query()->findOrFail($this->userId);
        $locks->lockWeek($target, Carbon::parse($this->weekStart), $u);
        session()->flash('success', 'Timesheet week locked.');
    }

    public function render(TimeEntryLockService $locks)
    {
        $u = Auth::user();
        abort_unless($u && ($u->isAdmin() || $u->isStaff()), 403);

        $users = User::query()->orderBy('name')->get(['id', 'name']);

        $ws = Carbon::parse($this->weekStart ?: now()->toDateString())->startOfWeek(Carbon::MONDAY)->startOfDay();
        $we = $ws->copy()->addDays(7);

        $entries = TimeEntry::query()
            ->when($this->userId, fn ($q) => $q->where('user_id', $this->userId))
            ->whereNotNull('ended_at')
            ->where('started_at', '>=', $ws)
            ->where('started_at', '<', $we)
            ->with(['user', 'request', 'task', 'approver'])
            ->orderByDesc('started_at')
            ->limit(300)
            ->get();

        $isLocked = false;
        if ($this->userId) {
            $target = User::query()->find($this->userId);
            if ($target) {
                $isLocked = $locks->isLocked($target, $ws);
            }
        }

        $lockRow = ($this->userId)
            ? TimeEntryLock::query()->where('user_id', $this->userId)->where('week_start', $ws->toDateString())->first()
            : null;

        return view('livewire.projects.time-approvals', compact('users', 'entries', 'ws', 'isLocked', 'lockRow'));
    }
}
