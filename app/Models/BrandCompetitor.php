<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandCompetitor extends Model
{
    protected $fillable = [
        'client_id',
        'competitor_name',
        'website_url',
        'positioning',
        'target_audience',
        'key_differentiators',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'key_differentiators' => 'array',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}

