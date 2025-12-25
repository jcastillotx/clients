<?php

namespace App\Jobs;

use App\Mail\ScheduledReportMail;
use App\Models\Payment;
use App\Models\ReportTemplate;
use App\Models\StorageConnection;
use App\Models\Request as ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendScheduledReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $templateId) {}

    public function handle(): void
    {
        $tpl = ReportTemplate::query()->find($this->templateId);
        if (!$tpl || !$tpl->is_active) {
            return;
        }

        $cfg = (array) ($tpl->config ?? []);
        $metric = (string) ($cfg['metric'] ?? '');
        $from = (string) ($cfg['from'] ?? now()->subDays(30)->toDateString());
        $to = (string) ($cfg['to'] ?? now()->toDateString());

        [$title, $headings, $rows] = $this->buildDataset($metric, $from, $to);

        $recipients = collect((array) ($tpl->recipients ?? []))
            ->map(fn ($e) => trim((string) $e))
            ->filter(fn ($e) => $e !== '' && Str::contains($e, '@'))
            ->unique()
            ->values()
            ->all();

        if (empty($recipients)) {
            return;
        }

        $filename = Str::slug($title) . "-{$from}-{$to}.csv";
        foreach ($recipients as $email) {
            Mail::to($email)->queue(new ScheduledReportMail($title, $from, $to, $headings, $rows, $filename));
        }

        $tpl->update([
            'last_sent_at' => now(),
            'next_send_at' => $this->nextRunAt((string) $tpl->schedule),
        ]);
    }

    /**
     * @return array{0:string,1:array<int,string>,2:array<int,array<int,mixed>>}
     */
    protected function buildDataset(string $metric, string $from, string $to): array
    {
        return match ($metric) {
            'revenue_by_month' => $this->revenueByMonth($from, $to),
            'requests_by_status' => $this->requestsByStatus($from, $to),
            'storage_usage_by_client' => $this->storageUsageByClient(),
            default => ['Custom report', ['Metric', 'Value'], [[$metric ?: 'unknown', 'unsupported metric']]],
        };
    }

    protected function revenueByMonth(string $from, string $to): array
    {
        $rows = Payment::query()
            ->where('status', 'succeeded')
            ->whereDate('processed_at', '>=', $from)
            ->whereDate('processed_at', '<=', $to)
            ->selectRaw("strftime('%Y-%m', processed_at) as ym, SUM(amount) as total")
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        if ($rows->isEmpty()) {
            $rows = Payment::query()
                ->where('status', 'succeeded')
                ->whereDate('processed_at', '>=', $from)
                ->whereDate('processed_at', '<=', $to)
                ->selectRaw("DATE_FORMAT(processed_at, '%Y-%m') as ym, SUM(amount) as total")
                ->groupBy('ym')
                ->orderBy('ym')
                ->get();
        }

        $headings = ['Month', 'Revenue'];
        $data = $rows->map(fn ($r) => [(string) $r->ym, (float) $r->total])->all();

        return ['Revenue by month', $headings, $data];
    }

    protected function requestsByStatus(string $from, string $to): array
    {
        $rows = ServiceRequest::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $headings = ['Status', 'Count'];
        $data = $rows->map(fn ($r) => [(string) $r->status, (int) $r->total])->all();
        return ['Requests by status', $headings, $data];
    }

    protected function storageUsageByClient(): array
    {
        $rows = StorageConnection::query()
            ->join('clients', 'clients.id', '=', 'storage_connections.client_id')
            ->selectRaw('clients.company_name as client, SUM(storage_connections.storage_used) as used, SUM(COALESCE(storage_connections.storage_limit, 0)) as known_limit')
            ->groupBy('clients.company_name')
            ->orderByDesc('used')
            ->get();

        $headings = ['Client', 'Used (bytes)', 'Known limit sum (bytes)'];
        $data = $rows->map(fn ($r) => [(string) $r->client, (int) $r->used, (int) $r->known_limit])->all();
        return ['Storage usage by client', $headings, $data];
    }

    protected function nextRunAt(string $schedule): ?\Illuminate\Support\Carbon
    {
        $schedule = $schedule ?: 'none';
        return match ($schedule) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            default => null,
        };
    }
}

