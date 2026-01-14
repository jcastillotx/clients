<?php

namespace App\Models;

use App\Services\Security\EncryptionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Data Room Model
 *
 * Represents a secure, isolated storage container with AES-256 encryption.
 * Each data room has its own encryption key and granular access controls.
 */
class DataRoom extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'created_by',
        'name',
        'description',
        'slug',
        'encryption_key',
        'encryption_algorithm',
        'key_derivation_salt',
        'storage_provider',
        'storage_bucket',
        'storage_prefix',
        'status',
        'require_2fa',
        'require_watermark',
        'allow_download',
        'allow_print',
        'session_timeout_minutes',
        'last_accessed_at',
        'locked_at',
        'locked_by',
        'lock_reason',
    ];

    protected function casts(): array
    {
        return [
            'require_2fa' => 'boolean',
            'require_watermark' => 'boolean',
            'allow_download' => 'boolean',
            'allow_print' => 'boolean',
            'session_timeout_minutes' => 'integer',
            'last_accessed_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DataRoom $room) {
            // Generate slug if not provided
            if (empty($room->slug)) {
                $room->slug = Str::slug($room->name).'-'.Str::random(8);
            }

            // Generate storage prefix for isolation
            if (empty($room->storage_prefix)) {
                $room->storage_prefix = 'data-rooms/'.$room->slug;
            }

            // Generate and encrypt the room's encryption key
            if (empty($room->encryption_key)) {
                $encryptionService = app(EncryptionService::class);
                $rawKey = $encryptionService->generateKey();
                $room->encryption_key = Crypt::encryptString($rawKey);
                $room->key_derivation_salt = $encryptionService->generateSalt();
            }
        });
    }

    /**
     * Get the decrypted encryption key.
     */
    public function getDecryptedKeyAttribute(): string
    {
        return Crypt::decryptString($this->encryption_key);
    }

    /**
     * Client that owns this data room.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * User who created this data room.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who locked this data room.
     */
    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * Files in this data room.
     */
    public function files(): HasMany
    {
        return $this->hasMany(DataRoomFile::class);
    }

    /**
     * Folders in this data room.
     */
    public function folders(): HasMany
    {
        return $this->hasMany(DataRoomFolder::class);
    }

    /**
     * Access grants for this data room.
     */
    public function accessGrants(): HasMany
    {
        return $this->hasMany(DataRoomAccess::class);
    }

    /**
     * Activity logs for this data room.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(DataRoomActivityLog::class);
    }

    /**
     * Pending invitations for this data room.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(DataRoomInvitation::class);
    }

    /**
     * Check if data room is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if data room is locked.
     */
    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    /**
     * Check if data room is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    /**
     * Lock the data room.
     */
    public function lock(User $user, ?string $reason = null): bool
    {
        return $this->update([
            'status' => 'locked',
            'locked_at' => now(),
            'locked_by' => $user->id,
            'lock_reason' => $reason,
        ]);
    }

    /**
     * Unlock the data room.
     */
    public function unlock(): bool
    {
        return $this->update([
            'status' => 'active',
            'locked_at' => null,
            'locked_by' => null,
            'lock_reason' => null,
        ]);
    }

    /**
     * Archive the data room.
     */
    public function archive(): bool
    {
        return $this->update(['status' => 'archived']);
    }

    /**
     * Check if a user has access to this data room.
     */
    public function userHasAccess(User $user, ?string $permission = null): bool
    {
        $access = $this->accessGrants()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $access) {
            return false;
        }

        if ($permission === null) {
            return true;
        }

        return $access->hasPermission($permission);
    }

    /**
     * Get the user's access record for this data room.
     */
    public function getUserAccess(User $user): ?DataRoomAccess
    {
        return $this->accessGrants()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Record last access time.
     */
    public function recordAccess(): void
    {
        $this->update(['last_accessed_at' => now()]);
    }

    /**
     * Get total file count.
     */
    public function getFileCountAttribute(): int
    {
        return $this->files()->where('status', 'active')->count();
    }

    /**
     * Get total storage used.
     */
    public function getTotalSizeAttribute(): int
    {
        return $this->files()->where('status', 'active')->sum('file_size');
    }

    /**
     * Get human-readable total size.
     */
    public function getHumanTotalSizeAttribute(): string
    {
        $bytes = $this->total_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Get active users with access.
     */
    public function getActiveUsersCountAttribute(): int
    {
        return $this->accessGrants()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->count();
    }
}
