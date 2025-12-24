<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Request extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'created_by',
        'assigned_to',
        'title',
        'description',
        'type',
        'status',
        'priority',
        'due_date',
        'estimated_hours',
        'actual_hours',
        'estimated_cost',
        'started_at',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'due_date' => 'date',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'due_date',
        'started_at',
        'completed_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the client that owns the request.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who created the request.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user assigned to the request.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Backwards-compatible alias.
     */
    public function assignee(): BelongsTo
    {
        return $this->assignedTo();
    }

    /**
     * Get attachments for this request.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(RequestAttachment::class);
    }

    /**
     * Get comments for this request.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(RequestComment::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get public comments (visible to clients).
     */
    public function publicComments(): HasMany
    {
        return $this->hasMany(RequestComment::class)
            ->where('is_internal', false)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get documents related to this request.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Get invoices related to this request.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Check if request is open.
     */
    public function isOpen(): bool
    {
        return !in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * Check if request is overdue.
     */
    public function isOverdue(): bool
    {
        if (!$this->due_date) {
            return false;
        }

        return $this->isOpen() && $this->due_date->isPast();
    }

    /**
     * Get the type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return config("client-portal.request_types.{$this->type}", ucfirst($this->type));
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return config("client-portal.request_statuses.{$this->status}", ucfirst(str_replace('_', ' ', $this->status)));
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
            'pending' => 'warning',
            'in_review' => 'info',
            'approved' => 'primary',
            'in_progress' => 'info',
            'on_hold' => 'secondary',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get priority badge HTML for UI.
     */
    public function getPriorityBadgeAttribute(): string
    {
        $label = ucfirst($this->priority);
        $class = match ($this->priority) {
            'low' => 'bg-success',
            'medium' => 'bg-info',
            'high' => 'bg-warning',
            'urgent' => 'bg-danger',
            default => 'bg-secondary',
        };

        return sprintf('<span class="badge %s">%s</span>', $class, $label);
    }

    /**
     * Backwards-compatible priority color helper.
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
     * Scope for requests by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for requests by priority.
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for requests for a client.
     */
    public function scopeForClient($query, int|Client $client)
    {
        $clientId = $client instanceof Client ? $client->id : $client;
        return $query->where('client_id', $clientId);
    }

    /**
     * Backwards-compatible aliases.
     */
    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->byStatus($status);
    }

    public function scopePriority($query, string $priority)
    {
        return $query->byPriority($priority);
    }

    /**
     * Scope for overdue requests.
     */
    public function scopeOverdue($query)
    {
        return $query->open()
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::updating(function (Request $request) {
            if (!$request->isDirty('status')) {
                return;
            }

            $from = $request->getOriginal('status');
            $to = $request->status;

            if ($from === 'completed' && $to === 'draft') {
                throw ValidationException::withMessages([
                    'status' => "Status cannot transition from '{$from}' to '{$to}'.",
                ]);
            }
        });
    }
}
