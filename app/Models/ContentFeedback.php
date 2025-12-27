<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentFeedback extends Model
{
    protected $table = 'content_feedback';

    protected $fillable = [
        'content_id',
        'user_id',
        'feedback_text',
        'feedback_type',
        'is_resolved',
        'resolved_by',
        'resolved_at',
        'meta',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'meta' => 'array',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(ContentCalendarItem::class, 'content_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Mark feedback as resolved
     */
    public function resolve(int $userId): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_by' => $userId,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Check if this is a revision request
     */
    public function isRevisionRequest(): bool
    {
        return $this->feedback_type === 'revision_request';
    }

    /**
     * Check if this is a comment
     */
    public function isComment(): bool
    {
        return $this->feedback_type === 'comment';
    }

    /**
     * Check if this is an approval note
     */
    public function isApprovalNote(): bool
    {
        return $this->feedback_type === 'approval_note';
    }

    /**
     * Scope for unresolved feedback
     */
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope for revision requests
     */
    public function scopeRevisionRequests($query)
    {
        return $query->where('feedback_type', 'revision_request');
    }
}
