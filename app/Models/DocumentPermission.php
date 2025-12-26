<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'role',
        'user_id',
        'can_view',
        'can_download',
        'can_upload_version',
        'can_delete',
        'can_share',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'can_view' => 'boolean',
        'can_download' => 'boolean',
        'can_upload_version' => 'boolean',
        'can_delete' => 'boolean',
        'can_share' => 'boolean',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

