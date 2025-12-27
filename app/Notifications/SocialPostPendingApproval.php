<?php

namespace App\Notifications;

use App\Models\ContentCalendarItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SocialPostPendingApproval extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ContentCalendarItem $post
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('social.pending-approvals');

        return (new MailMessage)
            ->subject('New Social Media Post Awaiting Your Approval')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('A new social media post is ready for your review and approval.')
            ->line('**Platform:** '.ucfirst($this->post->platform))
            ->line('**Title:** '.$this->post->title)
            ->line('**Preview:** '.Str::limit($this->post->content_text, 100))
            ->action('Review & Approve Post', $url)
            ->line('Please review the post and either approve it or request changes.')
            ->line('Thank you!');
    }

    public function toArray($notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'title' => $this->post->title,
            'platform' => $this->post->platform,
            'message' => 'New social media post awaiting your approval',
        ];
    }
}
