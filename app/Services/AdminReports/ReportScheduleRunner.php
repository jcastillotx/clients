<?php

namespace App\Services\AdminReports;

use App\Mail\ScheduledReportMail;
use App\Models\ReportSchedule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReportScheduleRunner
{
    public function __construct(private readonly ReportDataService $data)
    {
    }

    public function runDueSchedules(): void
    {
        $due = ReportSchedule::query()
            ->with('template')
            ->where('is_active', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->orderBy('next_run_at')
            ->limit(25)
            ->get();

        foreach ($due as $schedule) {
            try {
                $config = $schedule->template?->config ?? [];
                $category = (string) ($config['category'] ?? 'financial');
                $filters = (array) ($config['filters'] ?? []);
                $metrics = (array) ($config['metrics'] ?? []);

                $payload = $this->data->build($category, $filters + [
                    'granularity' => $config['granularity'] ?? 'month',
                    'metrics' => $metrics,
                ]);

                $pdf = Pdf::loadView('admin.reports.exports.generic', [
                    'category' => $category,
                    'payload' => $payload,
                ])->setPaper('a4', 'portrait')->output();

                foreach ((array) $schedule->recipients as $recipient) {
                    Mail::to($recipient)->send(new ScheduledReportMail(
                        category: $category,
                        payload: $payload,
                        pdfBytes: $pdf,
                    ));
                }

                $schedule->update([
                    'last_run_at' => now(),
                    'next_run_at' => $this->nextRunAt($schedule->frequency),
                    'last_error' => null,
                ]);
            } catch (\Throwable $e) {
                Log::error('Report schedule failed', [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                ]);

                $schedule->update([
                    'last_run_at' => now(),
                    'next_run_at' => $this->nextRunAt($schedule->frequency),
                    'last_error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function nextRunAt(string $frequency): \DateTimeInterface
    {
        return match ($frequency) {
            'daily' => now()->addDay(),
            'monthly' => now()->addMonth(),
            default => now()->addWeek(),
        };
    }
}

