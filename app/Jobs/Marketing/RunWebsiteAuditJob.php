<?php

namespace App\Jobs\Marketing;

use App\Services\Marketing\WebsiteAuditorService;
use App\Models\WebsiteAudit;
use App\Models\WebsiteAuditSchedule;
use App\Mail\Marketing\WebsiteAuditCriticalIssuesMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class RunWebsiteAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param array{
     *   client_id?:?int,
     *   audit_type?:string,
     *   max_pages?:int,
     *   respect_robots?:bool,
     *   use_ai?:bool,
     *   ai_provider?:string,
     *   ai_model?:?string,
     *   competitors?:array<int,string>,
     * } $options
     */
    public function __construct(
        public string $url,
        public array $options = [],
        public ?int $scheduleId = null,
    )
    {
    }

    public function handle(WebsiteAuditorService $auditor): void
    {
        $report = $auditor->performFullAudit($this->url, $this->options);

        if (!$this->scheduleId) {
            return;
        }

        $schedule = WebsiteAuditSchedule::query()->find($this->scheduleId);
        if (!$schedule || !$schedule->is_active) {
            return;
        }

        $auditId = (int) data_get($report, 'meta.audit_id', 0);
        if ($auditId <= 0) {
            return;
        }

        $audit = WebsiteAudit::query()->with('issues')->find($auditId);
        if (!$audit || $audit->status !== 'completed') {
            return;
        }

        // Identify "new" critical issues vs previous completed audit (best-effort fingerprint).
        $prev = WebsiteAudit::query()
            ->where('status', 'completed')
            ->where('website_url', $audit->website_url)
            ->when($audit->client_id !== null, fn ($q) => $q->where('client_id', $audit->client_id))
            ->where('id', '!=', $audit->id)
            ->orderByDesc('completed_at')
            ->first();

        $currentCritical = $audit->issues->where('severity', 'critical')->values();
        if ($currentCritical->isEmpty()) {
            return;
        }

        $fingerprint = fn ($i) => sha1(
            (string) $i->category . '|' . (string) $i->issue_type . '|' . (string) ($i->affected_url ?? '') . '|' . substr((string) $i->description, 0, 200)
        );

        $prevSet = [];
        if ($prev && is_array($prev->report) && isset($prev->report['issues']) && is_array($prev->report['issues'])) {
            foreach ($prev->report['issues'] as $pi) {
                if (!is_array($pi)) continue;
                if (($pi['severity'] ?? null) !== 'critical') continue;
                $prevSet[sha1((string) ($pi['category'] ?? '') . '|' . (string) ($pi['issue_type'] ?? '') . '|' . (string) ($pi['affected_url'] ?? '') . '|' . substr((string) ($pi['description'] ?? ''), 0, 200))] = true;
            }
        }

        $newCritical = $currentCritical->filter(fn ($i) => !isset($prevSet[$fingerprint($i)]))->values();
        if ($newCritical->isEmpty()) {
            return;
        }

        $recipients = collect((array) ($schedule->recipients ?? []))
            ->map(fn ($e) => trim((string) $e))
            ->filter(fn ($e) => $e !== '' && str_contains($e, '@'))
            ->unique()
            ->values()
            ->all();

        if (empty($recipients)) {
            return;
        }

        foreach ($recipients as $email) {
            Mail::to($email)->queue(new WebsiteAuditCriticalIssuesMail(
                websiteUrl: $audit->website_url,
                auditId: $audit->id,
                scores: is_array($audit->scores) ? $audit->scores : [],
                newCriticalIssues: $newCritical->map(fn ($i) => $i->only(['category', 'issue_type', 'description', 'affected_url']))->all(),
            ));
        }
    }
}

