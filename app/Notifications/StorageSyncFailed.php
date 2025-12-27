<?php

namespace App\Notifications;

use App\Models\StorageConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StorageSyncFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly StorageConnection $connection,
        public readonly string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Storage sync failed: {$this->connection->name}")
            ->line("A sync attempt for \"{$this->connection->name}\" failed.")
            ->line($this->message)
            ->action('Open Storage Dashboard', route('storage.dashboard'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'storage_sync_failed',
            'connection_id' => $this->connection->id,
            'connection_name' => $this->connection->name,
            'provider' => $this->connection->provider,
            'message' => $this->message,
        ];
    }
}
