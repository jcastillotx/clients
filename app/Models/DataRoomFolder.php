<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Data Room Folder Model
 *
 * Represents a folder within a data room for organizing encrypted files.
 */
class DataRoomFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_room_id',
        'parent_id',
        'created_by',
        'name',
        'path',
        'depth',
    ];

    protected function casts(): array
    {
        return [
            'depth' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DataRoomFolder $folder) {
            // Calculate path and depth
            if ($folder->parent_id) {
                $parent = static::find($folder->parent_id);
                $folder->path = $parent->path.'/'.$folder->name;
                $folder->depth = $parent->depth + 1;
            } else {
                $folder->path = '/'.$folder->name;
                $folder->depth = 0;
            }
        });

        static::updating(function (DataRoomFolder $folder) {
            // Update path if name changed
            if ($folder->isDirty('name') || $folder->isDirty('parent_id')) {
                if ($folder->parent_id) {
                    $parent = static::find($folder->parent_id);
                    $folder->path = $parent->path.'/'.$folder->name;
                    $folder->depth = $parent->depth + 1;
                } else {
                    $folder->path = '/'.$folder->name;
                    $folder->depth = 0;
                }
            }
        });
    }

    /**
     * Data room this folder belongs to.
     */
    public function dataRoom(): BelongsTo
    {
        return $this->belongsTo(DataRoom::class);
    }

    /**
     * Parent folder.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(DataRoomFolder::class, 'parent_id');
    }

    /**
     * User who created this folder.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Child folders.
     */
    public function children(): HasMany
    {
        return $this->hasMany(DataRoomFolder::class, 'parent_id');
    }

    /**
     * Files in this folder.
     */
    public function files(): HasMany
    {
        return $this->hasMany(DataRoomFile::class, 'folder_id');
    }

    /**
     * Get all ancestors of this folder.
     */
    public function getAncestors(): array
    {
        $ancestors = [];
        $current = $this->parent;

        while ($current) {
            array_unshift($ancestors, $current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Get breadcrumb path.
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = $this->getAncestors();
        $breadcrumbs[] = $this;

        return $breadcrumbs;
    }

    /**
     * Get all descendants (recursive).
     */
    public function getAllDescendants(): \Illuminate\Support\Collection
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }

        return $descendants;
    }

    /**
     * Get file count including subfolders.
     */
    public function getTotalFileCountAttribute(): int
    {
        $count = $this->files()->where('status', 'active')->count();

        foreach ($this->children as $child) {
            $count += $child->total_file_count;
        }

        return $count;
    }

    /**
     * Get total size including subfolders.
     */
    public function getTotalSizeAttribute(): int
    {
        $size = $this->files()->where('status', 'active')->sum('file_size');

        foreach ($this->children as $child) {
            $size += $child->total_size;
        }

        return $size;
    }

    /**
     * Check if folder is empty.
     */
    public function isEmpty(): bool
    {
        return ! $this->files()->where('status', 'active')->exists()
            && ! $this->children()->exists();
    }

    /**
     * Check if this folder is an ancestor of another folder.
     */
    public function isAncestorOf(DataRoomFolder $folder): bool
    {
        $current = $folder->parent;

        while ($current) {
            if ($current->id === $this->id) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }

    /**
     * Scope for root folders.
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
}
