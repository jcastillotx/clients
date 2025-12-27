<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'storage_connection_id',
        'path',
        'filename',
        'extension',
        'mime_type',
        'size_bytes',
        'modified_at',
        'checksum',
        'meta',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'modified_at' => 'datetime',
        'meta' => 'array',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(StorageConnection::class, 'storage_connection_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(StorageTag::class, 'storage_file_tag', 'storage_file_id', 'storage_tag_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(StorageFileLink::class, 'storage_file_id');
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size_bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function getDownloadUrlAttribute(): ?string
    {
        try {
            return route('storage.files.download', $this);
        } catch (\Throwable) {
            return null;
        }
    }
}
