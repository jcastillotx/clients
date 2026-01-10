<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffTaskComment extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'content',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(StaffTask::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function logActivity(StaffTask $task, string $message): self
    {
        return self::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'content' => $message,
            'is_system' => true,
        ]);
    }
}
