<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MessageAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'disk',
        'path',
        'filename',
        'mime_type',
        'size_bytes',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function getDownloadUrlAttribute(): string
    {
        // For now, use a signed temporary URL if supported; otherwise rely on controller in the future.
        try {
            return Storage::disk($this->disk)->temporaryUrl($this->path, now()->addMinutes(10));
        } catch (\Throwable) {
            return Storage::disk($this->disk)->url($this->path);
        }
    }
}

