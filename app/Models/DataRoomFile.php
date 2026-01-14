<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Data Room File Model
 *
 * Represents an encrypted file stored in a data room.
 * Files are encrypted with AES-256-GCM before storage.
 */
class DataRoomFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'data_room_id',
        'uploaded_by',
        'folder_id',
        'name',
        'original_filename',
        'storage_path',
        'mime_type',
        'file_size',
        'encryption_iv',
        'encryption_tag',
        'checksum',
        'encrypted_checksum',
        'version',
        'parent_version_id',
        'status',
        'is_locked',
        'locked_by',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'version' => 'integer',
            'is_locked' => 'boolean',
            'locked_at' => 'datetime',
        ];
    }

    /**
     * Data room this file belongs to.
     */
    public function dataRoom(): BelongsTo
    {
        return $this->belongsTo(DataRoom::class);
    }

    /**
     * User who uploaded this file.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Folder containing this file.
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(DataRoomFolder::class, 'folder_id');
    }

    /**
     * User who locked this file.
     */
    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * Previous version of this file.
     */
    public function parentVersion(): BelongsTo
    {
        return $this->belongsTo(DataRoomFile::class, 'parent_version_id');
    }

    /**
     * Newer versions of this file.
     */
    public function childVersions(): HasMany
    {
        return $this->hasMany(DataRoomFile::class, 'parent_version_id');
    }

    /**
     * Activity logs for this file.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(DataRoomActivityLog::class, 'file_id');
    }

    /**
     * Check if file is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if file is the latest version.
     */
    public function isLatestVersion(): bool
    {
        return ! $this->childVersions()->exists();
    }

    /**
     * Get all versions of this file (including self).
     */
    public function getAllVersions()
    {
        // Find the root version
        $root = $this;
        while ($root->parentVersion) {
            $root = $root->parentVersion;
        }

        // Get all descendants
        return static::where('id', $root->id)
            ->orWhere('parent_version_id', $root->id)
            ->orWhereIn('parent_version_id', function ($query) use ($root) {
                $query->select('id')
                    ->from('data_room_files')
                    ->where('parent_version_id', $root->id);
            })
            ->orderBy('version')
            ->get();
    }

    /**
     * Lock the file.
     */
    public function lock(User $user): bool
    {
        return $this->update([
            'is_locked' => true,
            'locked_by' => $user->id,
            'locked_at' => now(),
        ]);
    }

    /**
     * Unlock the file.
     */
    public function unlock(): bool
    {
        return $this->update([
            'is_locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ]);
    }

    /**
     * Archive the file.
     */
    public function archive(): bool
    {
        return $this->update(['status' => 'archived']);
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Get file extension.
     */
    public function getExtensionAttribute(): string
    {
        return strtolower(pathinfo($this->original_filename, PATHINFO_EXTENSION));
    }

    /**
     * Get icon class based on file type.
     */
    public function getIconClassAttribute(): string
    {
        return match ($this->extension) {
            'pdf' => 'fas fa-file-pdf text-danger',
            'doc', 'docx' => 'fas fa-file-word text-primary',
            'xls', 'xlsx' => 'fas fa-file-excel text-success',
            'ppt', 'pptx' => 'fas fa-file-powerpoint text-warning',
            'zip', 'rar', '7z' => 'fas fa-file-archive text-secondary',
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'fas fa-file-image text-info',
            'mp4', 'mov', 'avi' => 'fas fa-file-video text-purple',
            'mp3', 'wav', 'flac' => 'fas fa-file-audio text-pink',
            'txt', 'md' => 'fas fa-file-alt text-muted',
            'csv' => 'fas fa-file-csv text-success',
            default => 'fas fa-file text-muted',
        };
    }

    /**
     * Check if file is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    /**
     * Check if file is a PDF.
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Check if file is a video.
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'video/');
    }

    /**
     * Get the full path including folder path.
     */
    public function getFullPathAttribute(): string
    {
        if ($this->folder) {
            return $this->folder->path.'/'.$this->name;
        }

        return $this->name;
    }

    /**
     * Scope for active files.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for files in a specific folder.
     */
    public function scopeInFolder($query, ?int $folderId)
    {
        if ($folderId === null) {
            return $query->whereNull('folder_id');
        }

        return $query->where('folder_id', $folderId);
    }
}
