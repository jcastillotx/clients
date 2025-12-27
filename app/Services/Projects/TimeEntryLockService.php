<?php

namespace App\Services\Projects;

use App\Models\TimeEntryLock;
use App\Models\User;
use Carbon\Carbon;

class TimeEntryLockService
{
    public function weekStart(Carbon $date): Carbon
    {
        return $date->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
    }

    public function isLocked(User $user, Carbon $date): bool
    {
        $ws = $this->weekStart($date)->toDateString();
        return TimeEntryLock::query()
            ->where('user_id', $user->id)
            ->where('week_start', $ws)
            ->whereNotNull('locked_at')
            ->exists();
    }

    public function lockWeek(User $user, Carbon $weekStart, ?User $locker = null): TimeEntryLock
    {
        $ws = $this->weekStart($weekStart)->toDateString();

        return TimeEntryLock::query()->updateOrCreate(
            ['user_id' => $user->id, 'week_start' => $ws],
            ['locked_by' => $locker?->id, 'locked_at' => now()]
        );
    }
}

