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
        public string $csvFilename,
    ) {}

    public function build(): self
    {
        $csv = $this->toCsvString($this->headings, $this->rows);

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
            ->attachData($csv, $this->csvFilename, [
                'mime' => 'text/csv',
            ]);
    }

    /**
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, mixed>>  $rows
     */
    protected function toCsvString(array $headings, array $rows): string
    {
        $fh = fopen('php://temp', 'w+');
        fputcsv($fh, $headings);
        foreach ($rows as $r) {
            fputcsv($fh, $r);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return $csv ?: '';
    }
}

