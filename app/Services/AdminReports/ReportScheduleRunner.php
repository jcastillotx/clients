<?php

namespace App\Services\AdminReports;

use App\Mail\ScheduledReportMail;
use App\Models\ReportDelivery;
use App\Models\ReportSchedule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportScheduleRunner
{
    public function __construct(private readonly ReportDataService $data) {}

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

                $filename = sprintf(
                    'report_%s_%s_%s.pdf',
                    Str::slug($category),
                    (string) ($payload['meta']['start'] ?? now()->subDays(30)->toDateString()),
                    (string) ($payload['meta']['end'] ?? now()->toDateString()),
                );
                $path = 'scheduled/'.$schedule->id.'/'.$filename;
                Storage::disk('reports')->put($path, $pdf);

                $delivery = ReportDelivery::create([
                    'report_schedule_id' => $schedule->id,
                    'report_template_id' => $schedule->report_template_id,
                    'client_id' => $filters['client_id'] ?? null,
                    'category' => $category,
                    'meta' => $payload['meta'] ?? null,
                    'disk' => 'reports',
                    'path' => $path,
                    'recipients' => (array) $schedule->recipients,
                    'generated_at' => now(),
                    'status' => 'generated',
                ]);

                foreach ((array) $schedule->recipients as $recipient) {
                    Mail::to($recipient)->send(new ScheduledReportMail(
                        category: $category,
                        payload: $payload,
                        pdfBytes: $pdf,
                    ));
                }

                $delivery->update([
                    'sent_at' => now(),
                    'status' => 'sent',
                ]);

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

                try {
                    ReportDelivery::create([
                        'report_schedule_id' => $schedule->id,
                        'report_template_id' => $schedule->report_template_id,
                        'client_id' => null,
                        'category' => $schedule->template?->config['category'] ?? null,
                        'meta' => null,
                        'disk' => 'reports',
                        'path' => 'scheduled/'.$schedule->id.'/failed_'.Str::uuid().'.txt',
                        'recipients' => (array) $schedule->recipients,
                        'generated_at' => now(),
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ]);
                } catch (\Throwable) {
                    // ignore secondary failure
                }

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
