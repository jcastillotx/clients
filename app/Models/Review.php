<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'client_id',
        'platform',
        'reviewer_name',
        'rating',
        'review_text',
        'review_url',
        'sentiment',
        'responded_at',
        'response_text',
        'meta',
    ];

    protected $casts = [
        'rating' => 'integer',
        'responded_at' => 'datetime',
        'meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
