<?php

namespace App\Notifications;

use App\Models\StorageConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StorageQuotaWarning extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly StorageConnection $connection,
        public readonly int $percent,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Storage quota warning: {$this->percent}% used")
            ->line("Your storage connection \"{$this->connection->name}\" is {$this->percent}% full.")
            ->line('Consider increasing your quota, cleaning up old files, or switching your primary provider.')
            ->action('Open Storage Dashboard', route('storage.dashboard'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'storage_quota_warning',
            'connection_id' => $this->connection->id,
            'connection_name' => $this->connection->name,
            'provider' => $this->connection->provider,
            'percent' => $this->percent,
            'used_bytes' => $this->connection->used_bytes,
            'quota_bytes' => $this->connection->quota_bytes,
        ];
    }
}
