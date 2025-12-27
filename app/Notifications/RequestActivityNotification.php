<?php

namespace App\Notifications;

use App\Models\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Request $request,
        public string $action // created|updated
    ) {}

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
        $title = match ($this->action) {
            'created' => 'New request created',
            'updated' => 'Request updated',
            default => 'Request notification',
        };

        $url = null;
        try {
            if (method_exists($notifiable, 'isClient') && ! $notifiable->isClient()) {
                $url = route('admin.requests.show', $this->request);
            } else {
                $url = route('requests.show', $this->request);
            }
        } catch (\Throwable $e) {
            $url = route('requests.show', $this->request);
        }

        return (new MailMessage)
            ->subject($title.' · #'.$this->request->id)
            ->greeting('Hello!')
            ->line("A request was {$this->action}.")
            ->line("Title: {$this->request->title}")
            ->line("Type: {$this->request->type}")
            ->line("Priority: {$this->request->priority}")
            ->line("Status: {$this->request->status}")
            ->action('View request', $url)
            ->line('— '.config('app.name'));
    }
}
