<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backlink extends Model
{
    protected $fillable = [
        'client_id',
        'source_url',
        'source_url_hash',
        'target_url',
        'target_url_hash',
        'anchor_text',
        'domain_authority',
        'link_type',
        'first_seen_at',
        'last_checked_at',
        'status',
        'meta',
    ];

    protected static function booted(): void
    {
        static::saving(function (Backlink $backlink) {
            $backlink->source_url_hash = hash('sha256', $backlink->source_url);
            $backlink->target_url_hash = hash('sha256', $backlink->target_url);
        });
    }

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
