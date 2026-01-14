<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Data Room Activity Log Model
 *
 * SOC2 Type II compliant audit trail for data room activities.
 * Records all access, modifications, and security events.
 */
class DataRoomActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'data_room_id',
        'user_id',
        'file_id',
        'action',
        'resource_type',
        'resource_id',
        'details',
        'ip_address',
        'user_agent',
        'session_id',
        'was_2fa_verified',
        'status',
        'failure_reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'was_2fa_verified' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Action types for audit logging.
     */
    public const ACTIONS = [
        // File operations
        'file_viewed' => 'File Viewed',
        'file_downloaded' => 'File Downloaded',
        'file_uploaded' => 'File Uploaded',
        'file_edited' => 'File Edited',
        'file_deleted' => 'File Deleted',
        'file_restored' => 'File Restored',
        'file_locked' => 'File Locked',
        'file_unlocked' => 'File Unlocked',
        'file_versioned' => 'New Version Created',

        // Folder operations
        'folder_created' => 'Folder Created',
        'folder_renamed' => 'Folder Renamed',
        'folder_moved' => 'Folder Moved',
        'folder_deleted' => 'Folder Deleted',

        // Access operations
        'access_granted' => 'Access Granted',
        'access_revoked' => 'Access Revoked',
        'access_modified' => 'Access Modified',
        'invitation_sent' => 'Invitation Sent',
        'invitation_accepted' => 'Invitation Accepted',
        'invitation_expired' => 'Invitation Expired',

        // Room operations
        'room_accessed' => 'Room Accessed',
        'room_created' => 'Room Created',
        'room_updated' => 'Room Updated',
        'room_locked' => 'Room Locked',
        'room_unlocked' => 'Room Unlocked',
        'room_archived' => 'Room Archived',

        // Security events
        'login_attempt' => 'Login Attempt',
        'login_failed' => 'Login Failed',
        '2fa_verified' => '2FA Verified',
        '2fa_failed' => '2FA Failed',
        'ip_blocked' => 'IP Blocked',
        'session_expired' => 'Session Expired',
    ];

    protected static function booted(): void
    {
        static::creating(function (DataRoomActivityLog $log) {
            if (empty($log->created_at)) {
                $log->created_at = now();
            }
        });
    }

    /**
     * Data room this log belongs to.
     */
    public function dataRoom(): BelongsTo
    {
        return $this->belongsTo(DataRoom::class);
    }

    /**
     * User who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * File related to this log entry.
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(DataRoomFile::class, 'file_id');
    }

    /**
     * Get human-readable action name.
     */
    public function getActionLabelAttribute(): string
    {
        return self::ACTIONS[$this->action] ?? ucwords(str_replace('_', ' ', $this->action));
    }

    /**
     * Check if action was successful.
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if action failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if action was blocked.
     */
    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'success' => 'badge-success',
            'failed' => 'badge-danger',
            'blocked' => 'badge-warning',
            default => 'badge-secondary',
        };
    }

    /**
     * Create a log entry for an action.
     */
    public static function log(
        DataRoom $room,
        string $action,
        string $resourceType,
        ?int $resourceId = null,
        array $details = [],
        string $status = 'success',
        ?string $failureReason = null
    ): self {
        $request = request();

        return self::create([
            'data_room_id' => $room->id,
            'user_id' => auth()->id(),
            'file_id' => $resourceType === 'file' ? $resourceId : null,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'details' => $details,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'session_id' => session()->getId(),
            'was_2fa_verified' => session('2fa_verified', false),
            'status' => $status,
            'failure_reason' => $failureReason,
        ]);
    }

    /**
     * Scope for successful actions.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope for failed actions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for specific action type.
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for date range.
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }
}
