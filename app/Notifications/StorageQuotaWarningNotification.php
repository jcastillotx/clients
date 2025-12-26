<?php

namespace App\Notifications;

use App\Models\StorageConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StorageQuotaWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public StorageConnection $connection,
        public int $usedBytes,
        public ?int $totalBytes,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $provider = match ($this->connection->provider) {
            'aws_s3' => 'AWS S3',
            'dropbox' => 'Dropbox',
            'google_drive' => 'Google Drive',
            default => $this->connection->provider,
        };

        $percent = $this->totalBytes && $this->totalBytes > 0
            ? (int) round(($this->usedBytes / $this->totalBytes) * 100)
            : null;

        $line = $percent !== null
            ? "Your {$provider} storage is {$percent}% full."
            : "Your {$provider} storage is running low.";

        return (new MailMessage())
            ->subject('Storage quota warning · ' . config('app.name'))
            ->greeting('Heads up')
            ->line($line)
            ->line('Consider freeing up space or increasing your storage quota to avoid sync issues.')
            ->action('Manage storage', route('dashboard'))
            ->line('If you have questions, reply to this email.');
    }
}

