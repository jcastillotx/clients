<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Data Room Access Model
 *
 * Manages granular role-based access control for data rooms.
 * Supports permission levels and individual permission overrides.
 */
class DataRoomAccess extends Model
{
    use HasFactory;

    protected $table = 'data_room_access';

    protected $fillable = [
        'data_room_id',
        'user_id',
        'granted_by',
        'permission_level',
        'can_view',
        'can_download',
        'can_upload',
        'can_edit',
        'can_delete',
        'can_share',
        'can_manage_access',
        'allowed_ips',
        'allowed_folders',
        'expires_at',
        'require_2fa',
        'is_active',
        'last_accessed_at',
        'access_count',
    ];

    protected function casts(): array
    {
        return [
            'can_view' => 'boolean',
            'can_download' => 'boolean',
            'can_upload' => 'boolean',
            'can_edit' => 'boolean',
            'can_delete' => 'boolean',
            'can_share' => 'boolean',
            'can_manage_access' => 'boolean',
            'allowed_ips' => 'array',
            'allowed_folders' => 'array',
            'expires_at' => 'datetime',
            'require_2fa' => 'boolean',
            'is_active' => 'boolean',
            'last_accessed_at' => 'datetime',
            'access_count' => 'integer',
        ];
    }

    /**
     * Permission level mappings.
     */
    public const PERMISSION_LEVELS = [
        'view' => ['can_view' => true],
        'download' => ['can_view' => true, 'can_download' => true],
        'upload' => ['can_view' => true, 'can_download' => true, 'can_upload' => true],
        'edit' => ['can_view' => true, 'can_download' => true, 'can_upload' => true, 'can_edit' => true],
        'delete' => ['can_view' => true, 'can_download' => true, 'can_upload' => true, 'can_edit' => true, 'can_delete' => true],
        'manage' => ['can_view' => true, 'can_download' => true, 'can_upload' => true, 'can_edit' => true, 'can_delete' => true, 'can_share' => true, 'can_manage_access' => true],
        'admin' => ['can_view' => true, 'can_download' => true, 'can_upload' => true, 'can_edit' => true, 'can_delete' => true, 'can_share' => true, 'can_manage_access' => true],
    ];

    protected static function booted(): void
    {
        static::creating(function (DataRoomAccess $access) {
            // Apply permission level defaults if granular permissions not set
            if ($access->permission_level && isset(self::PERMISSION_LEVELS[$access->permission_level])) {
                $defaults = self::PERMISSION_LEVELS[$access->permission_level];
                foreach ($defaults as $permission => $value) {
                    if ($access->$permission === null) {
                        $access->$permission = $value;
                    }
                }
            }
        });
    }

    /**
     * Data room this access grant belongs to.
     */
    public function dataRoom(): BelongsTo
    {
        return $this->belongsTo(DataRoom::class);
    }

    /**
     * User who has access.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * User who granted access.
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * Check if access is currently valid.
     */
    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if access has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        if (! $this->isValid()) {
            return false;
        }

        $permissionMap = [
            'view' => 'can_view',
            'download' => 'can_download',
            'upload' => 'can_upload',
            'edit' => 'can_edit',
            'delete' => 'can_delete',
            'share' => 'can_share',
            'manage_access' => 'can_manage_access',
        ];

        $attribute = $permissionMap[$permission] ?? null;

        if ($attribute === null) {
            return false;
        }

        return (bool) $this->$attribute;
    }

    /**
     * Check if IP is allowed.
     */
    public function isIpAllowed(string $ip): bool
    {
        if (empty($this->allowed_ips)) {
            return true;
        }

        foreach ($this->allowed_ips as $allowedIp) {
            if ($allowedIp === $ip) {
                return true;
            }

            // Check CIDR notation
            if (str_contains($allowedIp, '/')) {
                if ($this->ipMatchesCidr($ip, $allowedIp)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if folder is allowed.
     */
    public function isFolderAllowed(?int $folderId): bool
    {
        if (empty($this->allowed_folders)) {
            return true;
        }

        if ($folderId === null) {
            // Root folder - check if null is in allowed folders
            return in_array(null, $this->allowed_folders, true);
        }

        return in_array($folderId, $this->allowed_folders, true);
    }

    /**
     * Record access.
     */
    public function recordAccess(): void
    {
        $this->increment('access_count');
        $this->update(['last_accessed_at' => now()]);
    }

    /**
     * Revoke access.
     */
    public function revoke(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Extend access expiration.
     */
    public function extend(\DateTimeInterface $newExpiration): bool
    {
        return $this->update(['expires_at' => $newExpiration]);
    }

    /**
     * Update permission level and apply defaults.
     */
    public function setPermissionLevel(string $level): bool
    {
        if (! isset(self::PERMISSION_LEVELS[$level])) {
            return false;
        }

        $data = ['permission_level' => $level];
        foreach (self::PERMISSION_LEVELS[$level] as $permission => $value) {
            $data[$permission] = $value;
        }

        return $this->update($data);
    }

    /**
     * Get human-readable permission summary.
     */
    public function getPermissionSummaryAttribute(): string
    {
        $permissions = [];

        if ($this->can_view) {
            $permissions[] = 'View';
        }
        if ($this->can_download) {
            $permissions[] = 'Download';
        }
        if ($this->can_upload) {
            $permissions[] = 'Upload';
        }
        if ($this->can_edit) {
            $permissions[] = 'Edit';
        }
        if ($this->can_delete) {
            $permissions[] = 'Delete';
        }
        if ($this->can_share) {
            $permissions[] = 'Share';
        }
        if ($this->can_manage_access) {
            $permissions[] = 'Manage';
        }

        return implode(', ', $permissions);
    }

    /**
     * Check if IP matches CIDR notation.
     */
    private function ipMatchesCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - (int) $bits);
        $subnet &= $mask;

        return ($ip & $mask) === $subnet;
    }

    /**
     * Scope for active access grants.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope for expired access grants.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }
}
