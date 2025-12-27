<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentTheme extends Model
{
    protected $fillable = [
        'client_id',
        'theme_name',
        'description',
        'color',
        'assigned_days',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'assigned_days' => 'array',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}

