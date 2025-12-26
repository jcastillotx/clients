<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'version',
        'provider',
        'provider_file_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'checksum',
        'text_snapshot',
        'created_by',
    ];

    protected $casts = [
        'version' => 'integer',
        'file_size' => 'integer',
        'created_by' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

