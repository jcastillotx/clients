<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataPrivacyRequest extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'status',
        'notes',
        'processed_at',
        'meta',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

