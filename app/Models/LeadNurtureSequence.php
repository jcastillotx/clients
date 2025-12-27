<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadNurtureSequence extends Model
{
    protected $fillable = [
        'client_id',
        'sequence_name',
        'steps',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'steps' => 'array',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}

