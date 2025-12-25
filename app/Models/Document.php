<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'request_id',
        'uploaded_by',
        'title',
        'description',
        'filename',
        'original_filename',
        'file_path',
        'thumbnail_path',
        'mime_type',
        'file_size',
        'category',
        'is_public',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'file_size' => 'integer',
        'is_public' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the client that owns the document.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the request associated with the document.
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    /**
     * Get the user who uploaded the document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function syncedFile(): HasOne
    {
        return $this->hasOne(SyncedFile::class);
    }

    /**
     * Get the file URL.
     */
    public function getUrlAttribute(): string
    {
        return route('documents.download', $this);
    }

    /**
     * Download URL accessor.
     */
    public function getDownloadUrlAttribute(): string
    {
        return $this->url;
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

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file extension.
     */
    public function getExtensionAttribute(): string
    {
        return strtolower(pathinfo($this->original_filename, PATHINFO_EXTENSION));
    }

    /**
     * Get category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return config("client-portal.document_categories.{$this->category}", ucfirst($this->category));
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
            default => 'fas fa-file text-muted',
        };
    }

    /**
     * Scope for public documents.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for documents by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Delete file from storage when model is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (Document $document) {
            if (!$document->isForceDeleting()) {
                return;
            }
            try {
                Storage::disk('documents')->delete($document->file_path);
                if ($document->thumbnail_path) {
                    Storage::disk('documents')->delete($document->thumbnail_path);
                }
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
}
