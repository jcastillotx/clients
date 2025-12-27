<?php

namespace App\Mail\Marketing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WebsiteAuditCriticalIssuesMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string,mixed>  $scores
     * @param  array<int,array{category:string,issue_type:string,description:string,affected_url:?string}>  $newCriticalIssues
     */
    public function __construct(
        public string $websiteUrl,
        public int $auditId,
        public array $scores,
        public array $newCriticalIssues,
    ) {}

    public function build(): self
    {
        $subject = "New critical website audit issues · {$this->websiteUrl}";

        return $this->subject($subject)
            ->view('emails.marketing.website-audit-critical', [
                'websiteUrl' => $this->websiteUrl,
                'auditId' => $this->auditId,
                'scores' => $this->scores,
                'issues' => $this->newCriticalIssues,
            ])
            ->text('emails.text.marketing.website-audit-critical', [
                'websiteUrl' => $this->websiteUrl,
                'auditId' => $this->auditId,
                'scores' => $this->scores,
                'issues' => $this->newCriticalIssues,
            ]);
    }
}
