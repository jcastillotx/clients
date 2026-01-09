<?php

namespace App\Http\Livewire\SupportTickets;

use App\Models\ActivityLog;
use App\Models\MaintenancePlan;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\RequestActivityNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;

class SupportTicketCreate extends Component
{
    public string $subject = '';

    public string $category = 'general_question';

    public string $priority = 'medium';

    public string $description = '';

    public ?MaintenancePlan $activePlan = null;

    public bool $isBillable = true;

    public ?float $estimatedHourlyRate = null;

    protected function rules(): array
    {
        $categories = implode(',', array_keys(config('client-portal.support_ticket_categories', ['general_question' => 'General Question'])));
        $priorities = implode(',', array_keys(config('client-portal.request_priorities', ['medium' => 'Medium'])));

        return [
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:' . $categories],
            'priority' => ['required', 'in:' . $priorities],
            'description' => ['required', 'string'],
        ];
    }

    public function mount(): void
    {
        $user = auth()->user();

        if ($user && $user->client_id) {
            $this->activePlan = MaintenancePlan::where('client_id', $user->client_id)
                ->active()
                ->first();

            if ($this->activePlan) {
                $this->isBillable = false;
            } else {
                $this->isBillable = true;
                // Default hourly rate for non-plan clients
                $this->estimatedHourlyRate = 150.00;
            }
        }
    }

    public function updated(string $property): void
    {
        $this->validateOnly($property);
    }

    public function submit()
    {
        $this->validate();

        $user = auth()->user();

        if (! $user) {
            session()->flash('error', 'You must be logged in to create a support ticket.');

            return;
        }

        if (! $user->client_id) {
            session()->flash('error', 'Your account is not associated with a client. Please contact support.');

            return;
        }

        $ticket = SupportTicket::create([
            'client_id' => $user->client_id,
            'maintenance_plan_id' => $this->activePlan?->id,
            'created_by' => $user->id,
            'subject' => $this->subject,
            'description' => $this->description,
            'category' => $this->category,
            'priority' => $this->priority,
            'status' => 'open',
            'is_billable' => $this->isBillable,
            'hourly_rate' => $this->isBillable ? $this->estimatedHourlyRate : null,
        ]);

        ActivityLog::log(
            'Submitted support ticket: ' . $ticket->subject,
            $ticket,
            ['category' => $ticket->category, 'priority' => $ticket->priority, 'is_billable' => $ticket->is_billable],
            'created',
            'support_tickets'
        );

        // Notify admins
        $recipients = User::query()->role(['super_admin', 'admin'])->get();
        if ($recipients->isNotEmpty()) {
            // We could create a dedicated notification, but for now reuse the existing one
            // Notification::send($recipients, new SupportTicketCreatedNotification($ticket));
        }

        session()->flash('success', 'Support ticket submitted successfully! Our team will review it shortly.');

        return redirect()->route('support-tickets.show', $ticket);
    }

    public function render()
    {
        return view('livewire.support-tickets.create', [
            'categories' => config('client-portal.support_ticket_categories', []),
            'priorities' => config('client-portal.request_priorities', []),
        ]);
    }
}
