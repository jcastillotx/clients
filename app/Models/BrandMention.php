<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandMention extends Model
{
    protected $fillable = [
        'client_id',
        'platform',
        'mention_text',
        'sentiment',
        'author',
        'url',
        'posted_at',
        'meta',
        'responded_at',
        'responded_by',
        'response_notes',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
        'responded_at' => 'datetime',
        'meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function markAsResponded(?int $userId = null, ?string $notes = null): self
    {
        $this->update([
            'responded_at' => now(),
            'responded_by' => $userId,
            'response_notes' => $notes,
        ]);

        return $this;
    }

    public function isResponded(): bool
    {
        return $this->responded_at !== null;
    }

    public function scopeNeedsResponse($query)
    {
        return $query->where('sentiment', 'negative')
            ->whereNull('responded_at');
    }

    public function scopeResponded($query)
    {
        return $query->whereNotNull('responded_at');
    }
}
