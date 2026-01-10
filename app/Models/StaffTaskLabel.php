<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StaffTaskLabel extends Model
{
    protected $fillable = [
        'board_id',
        'name',
        'color',
        'description',
        'is_global',
    ];

    protected $casts = [
        'is_global' => 'boolean',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(StaffTaskBoard::class, 'board_id');
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(StaffTask::class, 'staff_task_label', 'label_id', 'task_id');
    }

    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    public function scopeForBoard($query, $boardId)
    {
        return $query->where(function ($q) use ($boardId) {
            $q->where('board_id', $boardId)->orWhere('is_global', true);
        });
    }
}
