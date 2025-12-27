<?php

namespace App\Notifications;

use App\Models\SurveyResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SurveyRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SurveyResponse $response) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = route('client.surveys.respond', $this->response->anonymous_token);

        return (new MailMessage)
            ->subject('Quick feedback request')
            ->greeting('Hello!')
            ->line('We’d love your feedback on the completed work.')
            ->action('Complete survey', $url)
            ->line('Thank you!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'survey',
            'survey_id' => $this->response->survey_id,
            'survey_response_id' => $this->response->id,
            'token' => $this->response->anonymous_token,
        ];
    }
}
