<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'subject_type',
        'subject_id',
        'can_view',
        'can_download',
        'can_upload_version',
        'can_delete',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_download' => 'boolean',
        'can_upload_version' => 'boolean',
        'can_delete' => 'boolean',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo('subject');
    }
}

