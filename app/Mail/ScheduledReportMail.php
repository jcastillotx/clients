<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ScheduledReportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function __construct(
        public string $title,
        public string $from,
        public string $to,
        public array $headings,
        public array $rows,
        public string $attachmentFilename,
        public string $attachmentMime,
        public string $attachmentContents,
    ) {}

    public function build(): self
    {
        return $this->subject('Scheduled report · ' . $this->title)
            ->view('emails.scheduled-report', [
                'title' => $this->title,
                'from' => $this->from,
                'to' => $this->to,
            ])
            ->text('emails.text.scheduled-report', [
                'title' => $this->title,
                'from' => $this->from,
                'to' => $this->to,
            ])
            ->attachData($this->attachmentContents, $this->attachmentFilename, [
                'mime' => $this->attachmentMime,
            ]);
    }
}

