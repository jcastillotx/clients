<?php

namespace App\Notifications;

use App\Models\ContentCalendarItem;
use App\Models\ContentFeedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SocialPostChangesRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ContentCalendarItem $post,
        public ContentFeedback $feedback
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('admin.social.posts.edit', $this->post->id);

        return (new MailMessage)
            ->subject('Client Requested Changes to Social Media Post')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A client has requested changes to a social media post.')
            ->line('**Client:** ' . $this->post->client->company_name)
            ->line('**Platform:** ' . ucfirst($this->post->platform))
            ->line('**Post Title:** ' . $this->post->title)
            ->line('**Client Feedback:**')
            ->line('"' . $this->feedback->feedback_text . '"')
            ->action('Review & Edit Post', $url)
            ->line('Please make the requested changes and resubmit for approval.')
            ->line('Thank you!');
    }

    public function toArray($notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'title' => $this->post->title,
            'client_name' => $this->post->client->company_name,
            'feedback' => $this->feedback->feedback_text,
            'message' => 'Client requested changes to social media post',
        ];
    }
}
