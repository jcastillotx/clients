<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentCalendarItem extends Model
{
    protected $table = 'content_calendar';

    protected $fillable = [
        'client_id',
        'title',
        'content_type',
        'platform',
        'scheduled_for',
        'published_at',
        'status',
        'content_text',
        'media_urls',
        'hashtags',
        'campaign_tag',
        'approved_by',
        'created_by',
        'meta',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'published_at' => 'datetime',
        'media_urls' => 'array',
        'meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(ContentFeedback::class, 'content_id');
    }

    /**
     * Submit for client approval
     */
    public function submitForApproval(): void
    {
        $this->update(['status' => 'pending_approval']);
    }

    /**
     * Approve the content
     */
    public function approve(int $userId): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
        ]);
    }

    /**
     * Request changes
     */
    public function requestChanges(int $userId, string $feedback): void
    {
        $this->update(['status' => 'needs_revision']);

        $this->feedback()->create([
            'user_id' => $userId,
            'feedback_text' => $feedback,
            'feedback_type' => 'revision_request',
        ]);
    }

    /**
     * Schedule the post
     */
    public function schedule(\DateTime $scheduledFor): void
    {
        $this->update([
            'status' => 'scheduled',
            'scheduled_for' => $scheduledFor,
        ]);
    }

    /**
     * Mark as published
     */
    public function markAsPublished(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $reason = null): void
    {
        $meta = $this->meta ?? [];
        if ($reason) {
            $meta['failure_reason'] = $reason;
        }

        $this->update([
            'status' => 'failed',
            'meta' => $meta,
        ]);
    }

    /**
     * Check if pending approval
     */
    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_approval';
    }

    /**
     * Check if approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if scheduled
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Check if published
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if needs revision
     */
    public function needsRevision(): bool
    {
        return $this->status === 'needs_revision';
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'secondary',
            'pending_approval' => 'warning',
            'approved' => 'success',
            'needs_revision' => 'info',
            'scheduled' => 'primary',
            'published' => 'success',
            'failed' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Get platform icon
     */
    public function getPlatformIconAttribute(): string
    {
        return match($this->platform) {
            'facebook' => 'fab fa-facebook',
            'instagram' => 'fab fa-instagram',
            'linkedin' => 'fab fa-linkedin',
            'x', 'twitter' => 'fab fa-x-twitter',
            'tiktok' => 'fab fa-tiktok',
            'pinterest' => 'fab fa-pinterest',
            default => 'fas fa-share-alt'
        };
    }

    /**
     * Get character limit for platform
     */
    public function getCharacterLimitAttribute(): int
    {
        return match($this->platform) {
            'x', 'twitter' => 280,
            'facebook' => 63206,
            'instagram' => 2200,
            'linkedin' => 3000,
            'tiktok' => 2200,
            default => 2000
        };
    }

    /**
     * Scopes
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }
}

