<?php

namespace App\Notifications;

use App\Models\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestAiAnalysisCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Request $request) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('admin.requests.show', $this->request);

        return (new MailMessage)
            ->subject('AI analysis complete · Request #'.$this->request->id)
            ->greeting('Hello!')
            ->line('AI analysis is ready for a new/updated request.')
            ->line("Title: {$this->request->title}")
            ->action('Review AI analysis', $url)
            ->line('— '.config('app.name'));
    }
}
