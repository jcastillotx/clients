<?php

namespace App\Jobs;

use App\Models\SupportTicket;
use App\Notifications\SlaBreachNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckSupportTicketSlaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->checkResponseSlaBreaches();
        $this->checkResolutionSlaBreaches();
        $this->processEscalations();
        $this->updatePausedTicketDurations();
    }

    /**
     * Check for tickets that have breached response SLA.
     */
    protected function checkResponseSlaBreaches(): void
    {
        $tickets = SupportTicket::responseSlaDue()->get();

        foreach ($tickets as $ticket) {
            $ticket->markResponseBreached();

            // Send notification
            $this->sendBreachNotification($ticket, 'response');

            Log::info('Support ticket response SLA breached', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'client_id' => $ticket->client_id,
                'priority' => $ticket->priority,
            ]);
        }
    }

    /**
     * Check for tickets that have breached resolution SLA.
     */
    protected function checkResolutionSlaBreaches(): void
    {
        $tickets = SupportTicket::resolutionSlaDue()->get();

        foreach ($tickets as $ticket) {
            $ticket->markResolutionBreached();

            // Send notification
            $this->sendBreachNotification($ticket, 'resolution');

            Log::info('Support ticket resolution SLA breached', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'client_id' => $ticket->client_id,
                'priority' => $ticket->priority,
            ]);
        }
    }

    /**
     * Process escalations for breached tickets.
     */
    protected function processEscalations(): void
    {
        $escalationConfig = config('client-portal.support_ticket_sla.escalation', []);

        if (! ($escalationConfig['enabled'] ?? false)) {
            return;
        }

        $tickets = SupportTicket::needsEscalation()->get();

        foreach ($tickets as $ticket) {
            $nextLevel = $ticket->escalation_level + 1;
            $levelConfig = $escalationConfig['levels'][$nextLevel] ?? null;

            if (! $levelConfig) {
                continue;
            }

            // Check if enough time has passed since breach for this escalation level
            $breachedAt = $ticket->sla_resolution_breached_at ?? $ticket->sla_response_breached_at;
            if (! $breachedAt) {
                continue;
            }

            $minutesSinceBreach = $breachedAt->diffInMinutes(now());
            $afterBreachMinutes = $levelConfig['after_breach_minutes'] ?? 0;

            if ($minutesSinceBreach >= $afterBreachMinutes) {
                // Check if we haven't already escalated recently (within 5 minutes)
                if ($ticket->last_escalated_at && $ticket->last_escalated_at->diffInMinutes(now()) < 5) {
                    continue;
                }

                $ticket->escalate();

                // Send escalation notification
                $this->sendEscalationNotification($ticket, $nextLevel, $levelConfig['notify'] ?? []);

                Log::info('Support ticket escalated', [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'escalation_level' => $nextLevel,
                ]);
            }
        }
    }

    /**
     * Update the paused duration for tickets currently paused.
     */
    protected function updatePausedTicketDurations(): void
    {
        // Get tickets that are currently paused and increment their pause duration
        $pausedTickets = SupportTicket::query()
            ->open()
            ->where('sla_paused', true)
            ->get();

        foreach ($pausedTickets as $ticket) {
            // Add 5 minutes (this job runs every 5 minutes)
            $ticket->increment('sla_paused_duration_minutes', 5);
        }
    }

    /**
     * Send breach notification to relevant users.
     */
    protected function sendBreachNotification(SupportTicket $ticket, string $type): void
    {
        $notification = new SlaBreachNotification($ticket, $type);

        // Notify assigned staff
        if ($ticket->assignedTo) {
            $ticket->assignedTo->notify($notification);
        }

        // Notify ticket creator (if staff)
        if ($ticket->creator && $ticket->creator->hasRole(['admin', 'staff'])) {
            $ticket->creator->notify($notification);
        }
    }

    /**
     * Send escalation notification to specified roles.
     */
    protected function sendEscalationNotification(SupportTicket $ticket, int $level, array $notifyRoles): void
    {
        $notification = new SlaBreachNotification($ticket, 'escalation', $level);

        foreach ($notifyRoles as $role) {
            switch ($role) {
                case 'assigned_staff':
                    if ($ticket->assignedTo) {
                        $ticket->assignedTo->notify($notification);
                    }
                    break;

                case 'ticket_creator':
                    if ($ticket->creator && $ticket->creator->hasRole(['admin', 'staff'])) {
                        $ticket->creator->notify($notification);
                    }
                    break;

                case 'team_lead':
                case 'manager':
                    // Notify all users with the specified role
                    $users = \App\Models\User::role($role)->get();
                    foreach ($users as $user) {
                        $user->notify($notification);
                    }
                    break;
            }
        }
    }
}
