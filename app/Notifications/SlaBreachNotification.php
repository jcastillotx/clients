<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SlaBreachNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public SupportTicket $ticket,
        public string $type = 'response', // 'response', 'resolution', or 'escalation'
        public ?int $escalationLevel = null
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->getSubject();
        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('SLA Alert');

        if ($this->type === 'escalation') {
            $message->line("Ticket #{$this->ticket->ticket_number} has been escalated to Level {$this->escalationLevel}.")
                ->line("Subject: {$this->ticket->subject}")
                ->line("Client: {$this->ticket->client?->company_name}")
                ->line("Priority: {$this->ticket->priority_label}")
                ->error();
        } elseif ($this->type === 'response') {
            $message->line("The response SLA has been breached for ticket #{$this->ticket->ticket_number}.")
                ->line("Subject: {$this->ticket->subject}")
                ->line("Client: {$this->ticket->client?->company_name}")
                ->line("Priority: {$this->ticket->priority_label}")
                ->line('This ticket requires immediate attention.')
                ->error();
        } else {
            $message->line("The resolution SLA has been breached for ticket #{$this->ticket->ticket_number}.")
                ->line("Subject: {$this->ticket->subject}")
                ->line("Client: {$this->ticket->client?->company_name}")
                ->line("Priority: {$this->ticket->priority_label}")
                ->line('This ticket requires immediate attention.')
                ->error();
        }

        return $message->action('View Ticket', route('admin.support-tickets.show', $this->ticket));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sla_breach',
            'breach_type' => $this->type,
            'escalation_level' => $this->escalationLevel,
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'client_id' => $this->ticket->client_id,
            'client_name' => $this->ticket->client?->company_name,
            'priority' => $this->ticket->priority,
            'message' => $this->getMessage(),
        ];
    }

    /**
     * Get the notification subject.
     */
    protected function getSubject(): string
    {
        return match ($this->type) {
            'escalation' => "[Level {$this->escalationLevel}] SLA Escalation: Ticket #{$this->ticket->ticket_number}",
            'response' => "[SLA Breach] Response Time Exceeded: Ticket #{$this->ticket->ticket_number}",
            'resolution' => "[SLA Breach] Resolution Time Exceeded: Ticket #{$this->ticket->ticket_number}",
            default => "[SLA Alert] Ticket #{$this->ticket->ticket_number}",
        };
    }

    /**
     * Get the notification message.
     */
    protected function getMessage(): string
    {
        return match ($this->type) {
            'escalation' => "Ticket #{$this->ticket->ticket_number} has been escalated to Level {$this->escalationLevel}",
            'response' => "Response SLA breached for ticket #{$this->ticket->ticket_number}",
            'resolution' => "Resolution SLA breached for ticket #{$this->ticket->ticket_number}",
            default => "SLA alert for ticket #{$this->ticket->ticket_number}",
        };
    }
}
