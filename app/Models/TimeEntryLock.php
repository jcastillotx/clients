<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntryLock extends Model
{
    protected $fillable = [
        'user_id',
        'week_start',
        'locked_by',
        'locked_at',
    ];

    protected $casts = [
        'week_start' => 'date',
        'locked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
}
