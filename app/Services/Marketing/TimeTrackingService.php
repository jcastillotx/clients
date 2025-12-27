<?php

namespace App\Services\Marketing;

use App\Models\Request as ServiceRequest;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\CarbonPeriod;

class TimeTrackingService
{
    public function startTimer(User $user, ServiceRequest $request, ?Task $task = null, array $options = []): TimeEntry
    {
        // Stop any existing running timer for the user (single active timer per user)
        $running = TimeEntry::query()
            ->where('user_id', $user->id)
            ->whereNull('ended_at')
            ->get();

        foreach ($running as $entry) {
            $this->stopTimer($entry);
        }

        return TimeEntry::create([
            'user_id' => $user->id,
            'request_id' => $request->id,
            'task_id' => $task?->id,
            'started_at' => now(),
            'ended_at' => null,
            'duration_minutes' => null,
            'description' => $options['description'] ?? null,
            'is_billable' => (bool) ($options['is_billable'] ?? true),
            'hourly_rate' => $options['hourly_rate'] ?? null,
            'status' => 'pending',
        ]);
    }

    public function stopTimer(TimeEntry $timeEntry): TimeEntry
    {
        if ($timeEntry->ended_at) {
            return $timeEntry;
        }

        $ended = now();
        $minutes = $timeEntry->started_at ? max(0, (int) $timeEntry->started_at->diffInMinutes($ended)) : null;

        $timeEntry->update([
            'ended_at' => $ended,
            'duration_minutes' => $minutes,
        ]);

        return $timeEntry->fresh();
    }

    public function calculateBillableHours(ServiceRequest $request): float
    {
        $minutes = (int) TimeEntry::query()
            ->where('request_id', $request->id)
            ->where('is_billable', true)
            ->sum('duration_minutes');

        return round($minutes / 60, 2);
    }

    /**
     * @return array<int, TimeEntry>
     */
    public function generateTimesheet(User $user, CarbonPeriod $dateRange): array
    {
        $start = $dateRange->getStartDate()->startOfDay();
        $end = $dateRange->getEndDate()->endOfDay();

        return TimeEntry::query()
            ->where('user_id', $user->id)
            ->whereBetween('started_at', [$start, $end])
            ->orderByDesc('started_at')
            ->get()
            ->all();
    }
}
