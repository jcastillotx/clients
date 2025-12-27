<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentUploadedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Document $document) {}

    /**
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New document uploaded · '.$this->document->title)
            ->greeting('Hello!')
            ->line('A client uploaded a new document.')
            ->line('Title: '.$this->document->title)
            ->line('Category: '.$this->document->category)
            ->line('Client ID: '.$this->document->client_id)
            ->action('View document', route('documents.show', $this->document))
            ->line('— '.config('app.name'));
    }
}
