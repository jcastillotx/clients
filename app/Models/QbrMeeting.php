<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QbrMeeting extends Model
{
    protected $fillable = [
        'client_id',
        'scheduled_date',
        'presentation_url',
        'notes',
        'action_items',
        'next_qbr_date',
        'completed_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'action_items' => 'array',
        'next_qbr_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}

