<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DocumentVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'version',
        'disk',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    protected $casts = [
        'version' => 'integer',
        'file_size' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getDownloadUrlAttribute(): ?string
    {
        try {
            return route('documents.versions.download', $this);
        } catch (\Throwable) {
            return null;
        }
    }

    public function existsOnDisk(): bool
    {
        try {
            return Storage::disk($this->disk)->exists($this->file_path);
        } catch (\Throwable) {
            return false;
        }
    }
}
