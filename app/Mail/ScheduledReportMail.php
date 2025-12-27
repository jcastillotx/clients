<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScheduledReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $category,
        public readonly array $payload,
        private readonly string $pdfBytes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('Scheduled Report: %s (%s)', ucfirst($this->category), now()->toDateString()),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'admin.reports.emails.scheduled',
            with: [
                'category' => $this->category,
                'meta' => $this->payload['meta'] ?? [],
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBytes, sprintf('report_%s.pdf', $this->category))
                ->withMime('application/pdf'),
        ];
    }
}
