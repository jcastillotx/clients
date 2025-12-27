<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandMention extends Model
{
    protected $fillable = [
        'client_id',
        'platform',
        'mention_text',
        'sentiment',
        'author',
        'url',
        'posted_at',
        'meta',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
        'meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
