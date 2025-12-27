<?php

namespace App\Notifications;

use App\Models\Proposal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProposalSentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Proposal $proposal) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('client.proposals.view', $this->proposal);

        return (new MailMessage)
            ->subject('Proposal ready · '.$this->proposal->proposal_number)
            ->greeting('Hello!')
            ->line('A proposal is ready for your review.')
            ->line("Proposal: {$this->proposal->title}")
            ->action('View proposal', $url)
            ->line('— '.config('app.name'));
    }
}
