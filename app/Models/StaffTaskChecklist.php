<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffTaskChecklist extends Model
{
    protected $fillable = [
        'task_id',
        'title',
        'is_completed',
        'sort_order',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(StaffTask::class, 'task_id');
    }

    public function toggle(): void
    {
        $this->update(['is_completed' => !$this->is_completed]);
    }
}
