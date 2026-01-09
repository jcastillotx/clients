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
        });
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
}
