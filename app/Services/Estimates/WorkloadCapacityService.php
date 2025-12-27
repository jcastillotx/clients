<?php

namespace App\Services\Estimates;

use App\Models\Request as ServiceRequest;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WorkloadCapacityService
{
    /**
     * Get current team workload summary
     *
     * @return array{
     *   total_staff: int,
     *   available_staff: int,
     *   total_capacity_hours_week: float,
     *   committed_hours_week: float,
     *   available_hours_week: float,
     *   utilization_pct: float,
     *   staff_breakdown: array,
     *   open_requests: int,
     *   in_progress_requests: int,
     *   estimated_backlog_hours: float
     * }
     */
    public function getCurrentWorkload(): array
    {
        $staff = $this->getActiveStaff();
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        $hoursPerWeekPerStaff = (float) Setting::getValue('capacity.hours_per_week', 40);
        $totalCapacity = $staff->count() * $hoursPerWeekPerStaff;

        $staffBreakdown = [];
        $totalCommitted = 0;

        foreach ($staff as $user) {
            $breakdown = $this->getStaffWorkload($user, $weekStart, $weekEnd);
            $staffBreakdown[] = $breakdown;
            $totalCommitted += $breakdown['committed_hours'];
        }

        // Get request backlog
        $openRequests = ServiceRequest::query()
            ->whereIn('status', ['pending', 'in_review', 'approved'])
            ->count();

        $inProgressRequests = ServiceRequest::query()
            ->where('status', 'in_progress')
            ->count();

        // Estimate backlog hours from open requests
        $backlogHours = ServiceRequest::query()
            ->whereIn('status', ['pending', 'in_review', 'approved', 'in_progress'])
            ->sum('estimated_hours') ?? 0;

        $availableHours = max(0, $totalCapacity - $totalCommitted);
        $utilization = $totalCapacity > 0 ? ($totalCommitted / $totalCapacity) * 100 : 0;

        return [
            'total_staff' => $staff->count(),
            'available_staff' => collect($staffBreakdown)->where('utilization_pct', '<', 80)->count(),
            'total_capacity_hours_week' => $totalCapacity,
            'committed_hours_week' => $totalCommitted,
            'available_hours_week' => $availableHours,
            'utilization_pct' => round($utilization, 1),
            'staff_breakdown' => $staffBreakdown,
            'open_requests' => $openRequests,
            'in_progress_requests' => $inProgressRequests,
            'estimated_backlog_hours' => (float) $backlogHours,
        ];
    }

    /**
     * Get workload for a specific staff member
     */
    public function getStaffWorkload(User $user, ?Carbon $weekStart = null, ?Carbon $weekEnd = null): array
    {
        $weekStart = $weekStart ?? Carbon::now()->startOfWeek();
        $weekEnd = $weekEnd ?? Carbon::now()->endOfWeek();

        $hoursPerWeek = (float) Setting::getValue('capacity.hours_per_week', 40);

        // Hours logged this week
        $loggedMinutes = TimeEntry::query()
            ->where('user_id', $user->id)
            ->whereBetween('started_at', [$weekStart, $weekEnd])
            ->sum('duration_minutes');
        $loggedHours = round($loggedMinutes / 60, 2);

        // Assigned tasks
        $taskCounts = Task::query()
            ->where('assigned_to', $user->id)
            ->selectRaw("SUM(CASE WHEN status = 'todo' THEN 1 ELSE 0 END) as todo")
            ->selectRaw("SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress")
            ->selectRaw("SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked")
            ->first();

        // Assigned requests
        $assignedRequests = ServiceRequest::query()
            ->where('assigned_to', $user->id)
            ->whereIn('status', ['in_progress', 'approved', 'in_review'])
            ->count();

        // Estimated remaining hours on assigned work
        $estimatedHours = ServiceRequest::query()
            ->where('assigned_to', $user->id)
            ->whereIn('status', ['in_progress', 'approved', 'in_review'])
            ->sum('estimated_hours') ?? 0;

        // Calculate committed hours (logged + estimated remaining)
        $committedHours = $loggedHours + (float) $estimatedHours;
        $utilization = $hoursPerWeek > 0 ? ($committedHours / $hoursPerWeek) * 100 : 0;

        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'capacity_hours' => $hoursPerWeek,
            'logged_hours' => $loggedHours,
            'estimated_remaining_hours' => (float) $estimatedHours,
            'committed_hours' => $committedHours,
            'available_hours' => max(0, $hoursPerWeek - $committedHours),
            'utilization_pct' => round(min(100, $utilization), 1),
            'tasks_todo' => (int) ($taskCounts?->todo ?? 0),
            'tasks_in_progress' => (int) ($taskCounts?->in_progress ?? 0),
            'tasks_blocked' => (int) ($taskCounts?->blocked ?? 0),
            'assigned_requests' => $assignedRequests,
            'status' => $this->getAvailabilityStatus($utilization),
        ];
    }

    /**
     * Estimate delivery timeline based on workload
     *
     * @param float $estimatedHours Hours needed for the project
     * @return array{
     *   estimated_start: string,
     *   estimated_completion: string,
     *   weeks_to_complete: float,
     *   confidence: string,
     *   factors: array
     * }
     */
    public function estimateDeliveryTimeline(float $estimatedHours, ?string $priority = 'medium'): array
    {
        $workload = $this->getCurrentWorkload();
        $factors = [];

        // Base calculation: hours / available capacity per week
        $availablePerWeek = max(1, $workload['available_hours_week']);
        $weeksNeeded = $estimatedHours / $availablePerWeek;

        // Adjust for utilization
        if ($workload['utilization_pct'] > 90) {
            $weeksNeeded *= 1.5;
            $factors[] = 'High team utilization (+50% buffer)';
        } elseif ($workload['utilization_pct'] > 75) {
            $weeksNeeded *= 1.25;
            $factors[] = 'Moderate team utilization (+25% buffer)';
        }

        // Adjust for backlog
        $backlogWeeks = $workload['estimated_backlog_hours'] / max(1, $workload['total_capacity_hours_week']);
        if ($backlogWeeks > 2) {
            $weeksNeeded += min($backlogWeeks * 0.5, 4);
            $factors[] = 'Significant backlog queue';
        }

        // Priority adjustment
        $startDelay = match ($priority) {
            'urgent' => 0,
            'high' => 0.5,
            'medium' => 1,
            'low' => 2,
            default => 1,
        };

        if ($priority === 'urgent') {
            $factors[] = 'Urgent priority - expedited start';
        } elseif ($priority === 'low') {
            $factors[] = 'Low priority - queued behind other work';
        }

        $startDate = Carbon::now()->addWeeks($startDelay);
        $completionDate = $startDate->copy()->addWeeks(ceil($weeksNeeded));

        // Confidence level
        $confidence = match (true) {
            $workload['utilization_pct'] > 90 => 'low',
            $workload['utilization_pct'] > 75 => 'medium',
            $weeksNeeded > 8 => 'medium',
            default => 'high',
        };

        return [
            'estimated_start' => $startDate->format('Y-m-d'),
            'estimated_completion' => $completionDate->format('Y-m-d'),
            'weeks_to_complete' => round($weeksNeeded, 1),
            'confidence' => $confidence,
            'factors' => $factors,
            'workload_summary' => [
                'team_utilization' => $workload['utilization_pct'] . '%',
                'available_hours_week' => $workload['available_hours_week'],
                'backlog_hours' => $workload['estimated_backlog_hours'],
            ],
        ];
    }

    /**
     * Get best available staff for assignment
     *
     * @return Collection<User>
     */
    public function getAvailableStaffForAssignment(int $limit = 5): Collection
    {
        $staff = $this->getActiveStaff();
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        $staffWithWorkload = $staff->map(function ($user) use ($weekStart, $weekEnd) {
            $workload = $this->getStaffWorkload($user, $weekStart, $weekEnd);
            $user->workload = $workload;
            return $user;
        });

        // Sort by availability (lowest utilization first)
        return $staffWithWorkload
            ->sortBy(fn ($u) => $u->workload['utilization_pct'])
            ->take($limit);
    }

    /**
     * Get active staff members
     */
    protected function getActiveStaff(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['super_admin', 'admin', 'staff']);
            })
            ->get();
    }

    /**
     * Get availability status label
     */
    protected function getAvailabilityStatus(float $utilization): string
    {
        return match (true) {
            $utilization >= 100 => 'overloaded',
            $utilization >= 90 => 'at_capacity',
            $utilization >= 75 => 'busy',
            $utilization >= 50 => 'moderate',
            default => 'available',
        };
    }
}
