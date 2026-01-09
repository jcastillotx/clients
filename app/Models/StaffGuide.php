<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffGuide extends Model
{
    use HasFactory;

    protected $table = 'staff_guides';

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'summary',
        'content',
        'checklist',
        'service_tier',
        'price',
        'commitment',
        'is_published',
        'author_id',
        'published_at',
    ];

    protected $casts = [
        'checklist' => 'array',
        'price' => 'decimal:2',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(StaffGuideCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function views(): HasMany
    {
        return $this->hasMany(StaffGuideView::class, 'guide_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeByServiceTier($query, string $tier)
    {
        return $query->where('service_tier', $tier);
    }

    public function getFormattedPriceAttribute(): ?string
    {
        return $this->price ? '$' . number_format($this->price, 2) : null;
    }

    public function recordView(?int $userId = null): void
    {
        $this->views()->create([
            'user_id' => $userId ?? auth()->id(),
        ]);
    }
}
