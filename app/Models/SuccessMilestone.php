<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuccessMilestone extends Model
{
    protected $fillable = [
        'client_id',
        'milestone_name',
        'target_date',
        'achieved_date',
        'metric_value',
        'status',
        'celebration_sent',
    ];

    protected $casts = [
        'target_date' => 'date',
        'achieved_date' => 'date',
        'celebration_sent' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}

