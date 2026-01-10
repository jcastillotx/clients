<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffTaskColumn extends Model
{
    protected $fillable = [
        'board_id',
        'name',
        'color',
        'icon',
        'sort_order',
        'wip_limit',
        'is_done_column',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'wip_limit' => 'integer',
        'is_done_column' => 'boolean',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(StaffTaskBoard::class, 'board_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(StaffTask::class, 'column_id')->orderBy('sort_order');
    }

    public function getTaskCountAttribute(): int
    {
        return $this->tasks()->count();
    }

    public function isOverWipLimit(): bool
    {
        if ($this->wip_limit === null) {
            return false;
        }

        return $this->task_count > $this->wip_limit;
    }
}
