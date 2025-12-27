<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backlink extends Model
{
    protected $fillable = [
        'client_id',
        'source_url',
        'target_url',
        'anchor_text',
        'domain_authority',
        'link_type',
        'first_seen_at',
        'last_checked_at',
        'status',
        'meta',
    ];

    protected $casts = [
        'domain_authority' => 'integer',
        'first_seen_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}

