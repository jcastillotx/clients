<?php

namespace App\Notifications;

use App\Models\BrandMention;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NegativeBrandMentionAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public BrandMention $mention,
        public string $clientName
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $platformName = ucwords(str_replace('_', ' ', $this->mention->platform));
        
        return (new MailMessage)
            ->subject("⚠️ Negative Review Alert: {$this->clientName}")
            ->greeting("Attention Required")
            ->line("A negative mention has been detected for **{$this->clientName}**.")
            ->line("**Platform:** {$platformName}")
            ->line("**Author:** " . ($this->mention->author ?? 'Unknown'))
            ->line("**Posted:** " . $this->mention->posted_at?->format('M d, Y g:i A'))
            ->line("**Content:**")
            ->line('"' . \Illuminate\Support\Str::limit($this->mention->mention_text, 300) . '"')
            ->when($this->mention->url, function ($message) {
                return $message->action('View Original', $this->mention->url);
            })
            ->line('Please review and respond promptly to maintain your brand reputation.')
            ->salutation('— Brand Monitoring System');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'negative_brand_mention',
            'mention_id' => $this->mention->id,
            'client_name' => $this->clientName,
            'platform' => $this->mention->platform,
            'author' => $this->mention->author,
            'excerpt' => \Illuminate\Support\Str::limit($this->mention->mention_text, 150),
            'url' => $this->mention->url,
            'posted_at' => $this->mention->posted_at?->toIso8601String(),
        ];
    }
}
