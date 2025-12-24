<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Request extends Model
{
    use HasFactory, SoftDeletes;

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
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'estimated_cost' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
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
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
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
     * Scope for open requests.
     */
    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Scope for requests by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for requests by priority.
     */
    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
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
}
