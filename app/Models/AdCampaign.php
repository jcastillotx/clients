<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdCampaign extends Model
{
    protected $fillable = [
        'client_id',
        'ad_account_id',
        'platform_campaign_id',
        'name',
        'objective',
        'status',
        'daily_budget',
        'lifetime_budget',
        'start_date',
        'end_date',
        'target_audience',
        'targeting_options',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'daily_budget' => 'decimal:2',
        'lifetime_budget' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'targeting_options' => 'array',
        'meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function adSets(): HasMany
    {
        return $this->hasMany(AdSet::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(AdMetric::class, 'entity_id')
            ->where('entity_type', 'campaign');
    }

    public function getTotalSpendAttribute(): float
    {
        return $this->metrics()->sum('spend');
    }

    public function getTotalConversionsAttribute(): int
    {
        return $this->metrics()->sum('conversions');
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'active' => 'bg-green-100 text-green-800',
            'paused' => 'bg-yellow-100 text-yellow-800',
            'draft' => 'bg-gray-100 text-gray-800',
            'completed' => 'bg-blue-100 text-blue-800',
            'archived' => 'bg-gray-100 text-gray-600',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
