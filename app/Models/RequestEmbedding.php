<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestEmbedding extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'request_embeddings';

    protected $fillable = [
        'request_id',
        'provider',
        'model',
        'content_hash',
        'embedding',
    ];

    protected $casts = [
        'embedding' => 'array',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }
}
