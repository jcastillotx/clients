<?php

namespace App\Notifications;

use App\Models\RequestEstimate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EstimateSentForApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public RequestEstimate $estimate)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $req = $this->estimate->request;
        $url = route('client.requests.estimate', $req);

        return (new MailMessage)
            ->subject('Estimate ready for approval · Request #' . $req->id)
            ->greeting('Hello!')
            ->line('Your project estimate is ready to review.')
            ->line("Request: {$req->title}")
            ->action('Review & approve estimate', $url)
            ->line('— ' . config('app.name'));
    }
}

