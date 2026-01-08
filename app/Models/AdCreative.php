<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdCreative extends Model
{
    protected $fillable = [
        'client_id',
        'name',
        'type',
        'primary_text',
        'headline',
        'description',
        'image_url',
        'video_url',
        'carousel_cards',
        'thumbnail_url',
        'asset_urls',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'carousel_cards' => 'array',
        'asset_urls' => 'array',
        'meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class, 'ad_creative_id');
    }

    public function getPreviewUrlAttribute(): ?string
    {
        return match ($this->type) {
            'image' => $this->image_url,
            'video' => $this->thumbnail_url ?? $this->video_url,
            'carousel' => $this->carousel_cards[0]['image_url'] ?? null,
            default => null,
        };
    }
}
