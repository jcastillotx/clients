<?php

namespace App\Notifications;

use App\Models\ContentCalendarItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SocialPostApproved extends Notification implements ShouldQueue
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
        $url = route('admin.social.content-calendar');

        return (new MailMessage)
            ->subject('Social Media Post Approved!')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Great news! A social media post has been approved by the client.')
            ->line('**Client:** ' . $this->post->client->company_name)
            ->line('**Platform:** ' . ucfirst($this->post->platform))
            ->line('**Post Title:** ' . $this->post->title)
            ->action('Schedule Post', $url)
            ->line('The post is ready to be scheduled for publishing.')
            ->line('Thank you!');
    }

    public function toArray($notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'title' => $this->post->title,
            'client_name' => $this->post->client->company_name,
            'platform' => $this->post->platform,
            'message' => 'Social media post approved by client',
        ];
    }
}
