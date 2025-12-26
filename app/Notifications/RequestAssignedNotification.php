<?php

namespace App\Notifications;

use App\Models\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Request $request
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Request assigned · #' . $this->request->id)
            ->greeting('Hello!')
            ->line('A request has been assigned to you.')
            ->line("Client: " . ($this->request->client?->company_name ?? ('Client #' . $this->request->client_id)))
            ->line("Title: {$this->request->title}")
            ->line("Priority: {$this->request->priority}")
            ->line("Status: {$this->request->status}")
            ->action('View request', route('admin.requests.show', $this->request))
            ->line('— ' . config('app.name'));
    }
}

