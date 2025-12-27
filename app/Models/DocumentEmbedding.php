<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentEmbedding extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'document_embeddings';

    protected $fillable = [
        'document_id',
        'provider',
        'model',
        'content_hash',
        'embedding',
    ];

    protected $casts = [
        'embedding' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}

