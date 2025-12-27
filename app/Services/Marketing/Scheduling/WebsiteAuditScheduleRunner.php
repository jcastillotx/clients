<?php

namespace App\Services\Marketing\Scheduling;

use App\Jobs\Marketing\RunWebsiteAuditJob;
use App\Models\WebsiteAuditSchedule;
use Illuminate\Support\Facades\Log;

class WebsiteAuditScheduleRunner
{
    public function runDueSchedules(): void
    {
        $due = WebsiteAuditSchedule::query()
            ->where('is_active', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->orderBy('next_run_at')
            ->limit(25)
            ->get();

        foreach ($due as $schedule) {
            try {
                RunWebsiteAuditJob::dispatch(
                    url: (string) $schedule->website_url,
                    options: [
                        'client_id' => $schedule->client_id,
                        'audit_type' => $schedule->audit_type,
                        'max_pages' => (int) $schedule->max_pages,
                        'competitors' => is_array($schedule->competitors) ? $schedule->competitors : [],
                    ],
                    scheduleId: $schedule->id,
                );

                $schedule->update([
                    'last_run_at' => now(),
                    'next_run_at' => $this->nextRunAt((string) $schedule->frequency),
                    'last_error' => null,
                ]);
            } catch (\Throwable $e) {
                Log::error('Website audit schedule failed', [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                ]);

                $schedule->update([
                    'last_run_at' => now(),
                    'next_run_at' => $this->nextRunAt((string) $schedule->frequency),
                    'last_error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function nextRunAt(string $frequency): \DateTimeInterface
    {
        return match ($frequency) {
            'monthly' => now()->addMonth(),
            default => now()->addWeek(), // weekly default
        };
    }
}
