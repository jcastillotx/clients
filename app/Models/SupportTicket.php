<?php

namespace App\Models;

use App\Models\Concerns\LogsActivityWithContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SupportTicket extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;
    use LogsActivityWithContext;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'maintenance_plan_id',
        'created_by',
        'assigned_to',
        'ticket_number',
        'subject',
        'description',
        'category',
        'status',
        'priority',
        'is_billable',
        'estimated_hours',
        'actual_hours',
        'hourly_rate',
        'invoice_id',
        'first_response_at',
        'resolved_at',
        'closed_at',
        // SLA fields
        'sla_response_due_at',
        'sla_resolution_due_at',
        'sla_response_breached',
        'sla_resolution_breached',
        'sla_response_breached_at',
        'sla_resolution_breached_at',
        'escalation_level',
        'last_escalated_at',
        'sla_paused',
        'sla_paused_duration_minutes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'is_billable' => 'boolean',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'deleted_at' => 'datetime',
        // SLA casts
        'sla_response_due_at' => 'datetime',
        'sla_resolution_due_at' => 'datetime',
        'sla_response_breached' => 'boolean',
        'sla_resolution_breached' => 'boolean',
        'sla_response_breached_at' => 'datetime',
        'sla_resolution_breached_at' => 'datetime',
        'escalation_level' => 'integer',
        'last_escalated_at' => 'datetime',
        'sla_paused' => 'boolean',
        'sla_paused_duration_minutes' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('support_tickets')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (SupportTicket $ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = static::generateTicketNumber();
            }

            // Auto-determine billable status based on maintenance plan
            if ($ticket->client_id && is_null($ticket->maintenance_plan_id)) {
                $activePlan = MaintenancePlan::where('client_id', $ticket->client_id)
                    ->active()
                    ->first();

                if ($activePlan) {
                    $ticket->maintenance_plan_id = $activePlan->id;
                    $ticket->is_billable = false;
                } else {
                    $ticket->is_billable = true;
                }
            } elseif ($ticket->maintenance_plan_id) {
                $ticket->is_billable = false;
            }

            // Set SLA due dates based on priority
            $ticket->calculateSlaDueDates();
        });

        static::updating(function (SupportTicket $ticket) {
            // Handle SLA pausing when status changes to waiting_on_client
            $pauseStatuses = config('client-portal.support_ticket_sla.pause_on_statuses', ['waiting_on_client']);

            if ($ticket->isDirty('status')) {
                $oldStatus = $ticket->getOriginal('status');
                $newStatus = $ticket->status;

                // Entering a pause status
                if (in_array($newStatus, $pauseStatuses) && ! in_array($oldStatus, $pauseStatuses)) {
                    $ticket->sla_paused = true;
                }

                // Leaving a pause status
                if (in_array($oldStatus, $pauseStatuses) && ! in_array($newStatus, $pauseStatuses) && $ticket->sla_paused) {
                    $ticket->sla_paused = false;
                    // Recalculate SLA due dates accounting for paused time
                    $ticket->extendSlaDueDates();
                }
            }

            // Recalculate SLA if priority changes
            if ($ticket->isDirty('priority') && ! $ticket->sla_response_breached && ! $ticket->sla_resolution_breached) {
                $ticket->calculateSlaDueDates();
            }
        });
    }

    /**
     * Calculate and set SLA due dates based on priority.
     */
    public function calculateSlaDueDates(): void
    {
        $slaTargets = config('client-portal.support_ticket_sla.targets', []);
        $priorityTargets = $slaTargets[$this->priority] ?? $slaTargets['medium'] ?? null;

        if (! $priorityTargets) {
            return;
        }

        $baseTime = $this->created_at ?? now();

        $this->sla_response_due_at = $baseTime->copy()->addHours($priorityTargets['response_hours']);
        $this->sla_resolution_due_at = $baseTime->copy()->addHours($priorityTargets['resolution_hours']);
    }

    /**
     * Extend SLA due dates when ticket is unpaused.
     */
    public function extendSlaDueDates(): void
    {
        $pausedMinutes = $this->sla_paused_duration_minutes;

        if ($pausedMinutes > 0) {
            if ($this->sla_response_due_at && ! $this->sla_response_breached) {
                $this->sla_response_due_at = $this->sla_response_due_at->addMinutes($pausedMinutes);
            }
            if ($this->sla_resolution_due_at && ! $this->sla_resolution_breached) {
                $this->sla_resolution_due_at = $this->sla_resolution_due_at->addMinutes($pausedMinutes);
            }
            $this->sla_paused_duration_minutes = 0;
        }
    }

    /**
     * Mark the response SLA as breached.
     */
    public function markResponseBreached(): void
    {
        if (! $this->sla_response_breached) {
            $this->update([
                'sla_response_breached' => true,
                'sla_response_breached_at' => now(),
            ]);
        }
    }

    /**
     * Mark the resolution SLA as breached.
     */
    public function markResolutionBreached(): void
    {
        if (! $this->sla_resolution_breached) {
            $this->update([
                'sla_resolution_breached' => true,
                'sla_resolution_breached_at' => now(),
            ]);
        }
    }

    /**
     * Escalate the ticket to the next level.
     */
    public function escalate(): void
    {
        $escalationConfig = config('client-portal.support_ticket_sla.escalation', []);
        $maxLevel = count($escalationConfig['levels'] ?? []);

        if ($this->escalation_level < $maxLevel) {
            $this->update([
                'escalation_level' => $this->escalation_level + 1,
                'last_escalated_at' => now(),
            ]);
        }
    }

    /**
     * Get the SLA status for display.
     */
    public function getSlaStatusAttribute(): string
    {
        if ($this->sla_resolution_breached) {
            return 'breached';
        }

        if ($this->sla_response_breached && ! $this->first_response_at) {
            return 'response_breached';
        }

        if ($this->sla_paused) {
            return 'paused';
        }

        // Check if approaching breach (within warning threshold)
        $warningThreshold = config('client-portal.support_ticket_sla.escalation.warning_threshold', 75);

        if (! $this->first_response_at && $this->sla_response_due_at) {
            $percentUsed = $this->getResponseSlaPercentUsed();
            if ($percentUsed >= $warningThreshold) {
                return 'warning';
            }
        }

        if ($this->sla_resolution_due_at && $this->isOpen()) {
            $percentUsed = $this->getResolutionSlaPercentUsed();
            if ($percentUsed >= $warningThreshold) {
                return 'warning';
            }
        }

        return 'on_track';
    }

    /**
     * Get the percentage of response SLA time used.
     */
    public function getResponseSlaPercentUsed(): float
    {
        if (! $this->sla_response_due_at || ! $this->created_at) {
            return 0;
        }

        $totalMinutes = $this->created_at->diffInMinutes($this->sla_response_due_at);
        if ($totalMinutes <= 0) {
            return 100;
        }

        $elapsedMinutes = $this->created_at->diffInMinutes(now()) - $this->sla_paused_duration_minutes;

        return min(100, ($elapsedMinutes / $totalMinutes) * 100);
    }

    /**
     * Get the percentage of resolution SLA time used.
     */
    public function getResolutionSlaPercentUsed(): float
    {
        if (! $this->sla_resolution_due_at || ! $this->created_at) {
            return 0;
        }

        $totalMinutes = $this->created_at->diffInMinutes($this->sla_resolution_due_at);
        if ($totalMinutes <= 0) {
            return 100;
        }

        $elapsedMinutes = $this->created_at->diffInMinutes(now()) - $this->sla_paused_duration_minutes;

        return min(100, ($elapsedMinutes / $totalMinutes) * 100);
    }

    /**
     * Get the SLA status color for UI.
     */
    public function getSlaStatusColorAttribute(): string
    {
        return match ($this->sla_status) {
            'breached', 'response_breached' => 'danger',
            'warning' => 'warning',
            'paused' => 'secondary',
            'on_track' => 'success',
            default => 'secondary',
        };
    }

    /**
     * Get time remaining until response SLA breach.
     */
    public function getResponseTimeRemainingAttribute(): ?string
    {
        if (! $this->sla_response_due_at || $this->first_response_at || $this->sla_response_breached) {
            return null;
        }

        $remaining = now()->diff($this->sla_response_due_at);

        if ($remaining->invert) {
            return 'Breached';
        }

        return $this->formatTimeDiff($remaining);
    }

    /**
     * Get time remaining until resolution SLA breach.
     */
    public function getResolutionTimeRemainingAttribute(): ?string
    {
        if (! $this->sla_resolution_due_at || ! $this->isOpen() || $this->sla_resolution_breached) {
            return null;
        }

        $remaining = now()->diff($this->sla_resolution_due_at);

        if ($remaining->invert) {
            return 'Breached';
        }

        return $this->formatTimeDiff($remaining);
    }

    /**
     * Format a DateInterval for display.
     */
    protected function formatTimeDiff(\DateInterval $diff): string
    {
        if ($diff->d > 0) {
            return $diff->d . 'd ' . $diff->h . 'h';
        }
        if ($diff->h > 0) {
            return $diff->h . 'h ' . $diff->i . 'm';
        }

        return $diff->i . 'm';
    }

    /**
     * Generate a unique ticket number.
     */
    public static function generateTicketNumber(): string
    {
        $prefix = 'TKT-';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));

        return $prefix . $date . '-' . $random;
    }

    /**
     * Get the client that owns the ticket.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the maintenance plan (if any).
     */
    public function maintenancePlan(): BelongsTo
    {
        return $this->belongsTo(MaintenancePlan::class);
    }

    /**
     * Get the user who created the ticket.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user assigned to the ticket.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the invoice (if billable and invoiced).
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get comments for this ticket.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(SupportTicketComment::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get public comments (visible to clients).
     */
    public function publicComments(): HasMany
    {
        return $this->hasMany(SupportTicketComment::class)
            ->where('is_internal', false)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Check if ticket is open.
     */
    public function isOpen(): bool
    {
        return ! in_array($this->status, ['resolved', 'closed']);
    }

    /**
     * Check if ticket is covered by maintenance plan.
     */
    public function isCoveredByPlan(): bool
    {
        return $this->maintenance_plan_id !== null && ! $this->is_billable;
    }

    /**
     * Calculate the billable amount.
     */
    public function getBillableAmountAttribute(): float
    {
        if (! $this->is_billable || ! $this->actual_hours || ! $this->hourly_rate) {
            return 0;
        }

        return (float) $this->actual_hours * (float) $this->hourly_rate;
    }

    /**
     * Get the category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return config("client-portal.support_ticket_categories.{$this->category}", ucfirst(str_replace('_', ' ', $this->category)));
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return config("client-portal.support_ticket_statuses.{$this->status}", ucfirst(str_replace('_', ' ', $this->status)));
    }

    /**
     * Get the priority label.
     */
    public function getPriorityLabelAttribute(): string
    {
        return config("client-portal.request_priorities.{$this->priority}", ucfirst($this->priority));
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'open' => 'warning',
            'in_progress' => 'info',
            'waiting_on_client' => 'secondary',
            'waiting_on_vendor' => 'secondary',
            'resolved' => 'success',
            'closed' => 'dark',
            default => 'secondary',
        };
    }

    /**
     * Get priority color for UI.
     */
    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'success',
            'medium' => 'info',
            'high' => 'warning',
            'urgent' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Scope for open tickets.
     */
    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', ['resolved', 'closed']);
    }

    /**
     * Scope for tickets by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for tickets by priority.
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for tickets for a client.
     */
    public function scopeForClient($query, int|Client $client)
    {
        $clientId = $client instanceof Client ? $client->id : $client;

        return $query->where('client_id', $clientId);
    }

    /**
     * Scope for billable tickets.
     */
    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    /**
     * Scope for covered (non-billable) tickets.
     */
    public function scopeCovered($query)
    {
        return $query->where('is_billable', false);
    }

    /**
     * Scope for tickets with response SLA approaching breach.
     */
    public function scopeResponseSlaDue($query)
    {
        return $query->open()
            ->whereNull('first_response_at')
            ->where('sla_response_breached', false)
            ->where('sla_paused', false)
            ->whereNotNull('sla_response_due_at')
            ->where('sla_response_due_at', '<=', now());
    }

    /**
     * Scope for tickets with resolution SLA approaching breach.
     */
    public function scopeResolutionSlaDue($query)
    {
        return $query->open()
            ->where('sla_resolution_breached', false)
            ->where('sla_paused', false)
            ->whereNotNull('sla_resolution_due_at')
            ->where('sla_resolution_due_at', '<=', now());
    }

    /**
     * Scope for tickets with any SLA breached.
     */
    public function scopeSlaBreached($query)
    {
        return $query->where(function ($q) {
            $q->where('sla_response_breached', true)
                ->orWhere('sla_resolution_breached', true);
        });
    }

    /**
     * Scope for tickets needing escalation.
     */
    public function scopeNeedsEscalation($query)
    {
        $escalationConfig = config('client-portal.support_ticket_sla.escalation', []);
        $maxLevel = count($escalationConfig['levels'] ?? []);

        return $query->open()
            ->slaBreached()
            ->where('escalation_level', '<', $maxLevel);
    }
}
