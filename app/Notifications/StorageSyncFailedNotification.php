<?php

namespace App\Notifications;

use App\Models\StorageConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StorageSyncFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public StorageConnection $connection,
        public string $errorMessage,
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

        return (new MailMessage())
            ->subject('Storage sync failed · ' . config('app.name'))
            ->greeting('We couldn’t sync your storage')
            ->line("Provider: {$provider}")
            ->line("Error: {$this->errorMessage}")
            ->line('We will retry automatically. You can also reconnect the provider if access was revoked.')
            ->action('Manage storage', route('dashboard'));
    }
}

