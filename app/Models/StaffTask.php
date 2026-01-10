<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffTask extends Model
{
    protected $fillable = [
        'board_id',
        'column_id',
        'parent_id',
        'title',
        'description',
        'priority',
        'start_date',
        'due_date',
        'estimated_hours',
        'actual_hours',
        'progress',
        'created_by',
        'client_id',
        'request_id',
        'sort_order',
        'completed_at',
        'meta',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'progress' => 'integer',
        'sort_order' => 'integer',
        'completed_at' => 'datetime',
        'meta' => 'array',
    ];

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const PRIORITIES = [
        self::PRIORITY_LOW => ['label' => 'Low', 'color' => 'slate'],
        self::PRIORITY_NORMAL => ['label' => 'Normal', 'color' => 'blue'],
        self::PRIORITY_HIGH => ['label' => 'High', 'color' => 'amber'],
        self::PRIORITY_URGENT => ['label' => 'Urgent', 'color' => 'red'],
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(StaffTaskBoard::class, 'board_id');
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(StaffTaskColumn::class, 'column_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(StaffTask::class, 'parent_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(StaffTask::class, 'parent_id')->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'staff_task_assignees', 'task_id', 'user_id')
            ->withPivot('role', 'assigned_at')
            ->withTimestamps();
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(StaffTaskLabel::class, 'staff_task_label', 'task_id', 'label_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(StaffTaskComment::class, 'task_id')->latest();
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(StaffTaskChecklist::class, 'task_id')->orderBy('sort_order');
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority]['label'] ?? 'Normal';
    }

    public function getPriorityColorAttribute(): string
    {
        return self::PRIORITIES[$this->priority]['color'] ?? 'blue';
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->due_date || $this->completed_at) {
            return false;
        }

        return $this->due_date->isPast();
    }

    public function getIsDueSoonAttribute(): bool
    {
        if (!$this->due_date || $this->completed_at || $this->is_overdue) {
            return false;
        }

        return $this->due_date->isBetween(now(), now()->addDays(2));
    }

    public function getChecklistProgressAttribute(): array
    {
        $total = $this->checklists()->count();
        $completed = $this->checklists()->where('is_completed', true)->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0,
        ];
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('due_date')
            ->whereNull('completed_at')
            ->where('due_date', '<', now());
    }

    public function scopeDueSoon($query, $days = 7)
    {
        return $query->whereNotNull('due_date')
            ->whereNull('completed_at')
            ->whereBetween('due_date', [now(), now()->addDays($days)]);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->whereHas('assignees', fn($q) => $q->where('user_id', $userId));
    }

    public function scopeUnassigned($query)
    {
        return $query->whereDoesntHave('assignees');
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'completed_at' => now(),
            'progress' => 100,
        ]);
    }

    public function markAsIncomplete(): void
    {
        $this->update([
            'completed_at' => null,
        ]);
    }
}
