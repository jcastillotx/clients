<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Data Room Invitation Model
 *
 * Manages secure invitations to data rooms with time-limited access tokens.
 */
class DataRoomInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_room_id',
        'invited_by',
        'email',
        'token',
        'permission_level',
        'permissions',
        'status',
        'expires_at',
        'accepted_at',
        'accepted_by',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DataRoomInvitation $invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(64);
            }

            if (empty($invitation->expires_at)) {
                // Default 7 day expiration
                $invitation->expires_at = now()->addDays(7);
            }
        });
    }

    /**
     * Data room this invitation is for.
     */
    public function dataRoom(): BelongsTo
    {
        return $this->belongsTo(DataRoom::class);
    }

    /**
     * User who sent the invitation.
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * User who accepted the invitation.
     */
    public function accepter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    /**
     * Check if invitation is still valid.
     */
    public function isValid(): bool
    {
        return $this->status === 'pending' && ! $this->isExpired();
    }

    /**
     * Check if invitation has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if invitation was accepted.
     */
    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if invitation was revoked.
     */
    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    /**
     * Accept the invitation for a user.
     */
    public function accept(User $user): DataRoomAccess
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
            'accepted_by' => $user->id,
        ]);

        // Create access grant
        return DataRoomAccess::create([
            'data_room_id' => $this->data_room_id,
            'user_id' => $user->id,
            'granted_by' => $this->invited_by,
            'permission_level' => $this->permission_level,
            'can_view' => $this->permissions['can_view'] ?? true,
            'can_download' => $this->permissions['can_download'] ?? false,
            'can_upload' => $this->permissions['can_upload'] ?? false,
            'can_edit' => $this->permissions['can_edit'] ?? false,
            'can_delete' => $this->permissions['can_delete'] ?? false,
            'can_share' => $this->permissions['can_share'] ?? false,
            'can_manage_access' => $this->permissions['can_manage_access'] ?? false,
            'is_active' => true,
        ]);
    }

    /**
     * Revoke the invitation.
     */
    public function revoke(): bool
    {
        return $this->update(['status' => 'revoked']);
    }

    /**
     * Extend expiration time.
     */
    public function extend(int $days = 7): bool
    {
        return $this->update([
            'expires_at' => now()->addDays($days),
        ]);
    }

    /**
     * Resend invitation (regenerate token and extend).
     */
    public function resend(): bool
    {
        return $this->update([
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
            'status' => 'pending',
        ]);
    }

    /**
     * Get invitation URL.
     */
    public function getUrlAttribute(): string
    {
        return route('data-rooms.invitation.accept', ['token' => $this->token]);
    }

    /**
     * Get time until expiration.
     */
    public function getExpiresInAttribute(): string
    {
        if ($this->isExpired()) {
            return 'Expired';
        }

        return $this->expires_at->diffForHumans();
    }

    /**
     * Find an invitation by token.
     */
    public static function findByToken(string $token): ?self
    {
        return static::where('token', $token)->first();
    }

    /**
     * Scope for pending invitations.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    /**
     * Scope for expired invitations.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
            ->where('status', 'pending');
    }

    /**
     * Mark expired invitations as expired.
     */
    public static function markExpired(): int
    {
        return static::expired()->update(['status' => 'expired']);
    }
}
