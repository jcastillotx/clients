<?php

namespace App\Services\Projects;

use App\Models\ProjectBudget;
use App\Models\Request as ServiceRequest;
use App\Models\TimeEntry;

class ProjectBudgetService
{
    public function recalcForRequest(ServiceRequest $request): ?ProjectBudget
    {
        $budget = ProjectBudget::query()->firstOrNew(['request_id' => $request->id]);

        $minutes = (int) TimeEntry::query()
            ->where('request_id', $request->id)
            ->sum('duration_minutes');

        $hours = round($minutes / 60, 2);

        $spentAmount = $budget->spent_amount ?? 0;
        // Best-effort: if hourly_rate is tracked, sum; otherwise keep existing spent_amount.
        $rateAmount = (float) TimeEntry::query()
            ->where('request_id', $request->id)
            ->whereNotNull('hourly_rate')
            ->selectRaw('SUM((duration_minutes / 60.0) * hourly_rate) as amt')
            ->value('amt');

        if ($rateAmount > 0) {
            $spentAmount = round($rateAmount, 2);
        }

        $budget->fill([
            'spent_hours' => $hours,
            'spent_amount' => $spentAmount,
            'is_exceeded' => ($budget->budget_hours !== null && $hours > (float) $budget->budget_hours)
                || ($budget->budget_amount !== null && $spentAmount > (float) $budget->budget_amount),
        ]);

        $budget->save();

        return $budget;
    }
}
