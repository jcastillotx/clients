<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeBaseDocument extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'knowledge_base_documents';

    protected $fillable = [
        'document_id',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
        'created_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
