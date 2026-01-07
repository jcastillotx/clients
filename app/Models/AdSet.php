<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdSet extends Model
{
    protected $fillable = [
        'ad_campaign_id',
        'platform_ad_set_id',
        'name',
        'status',
        'daily_budget',
        'lifetime_budget',
        'start_date',
        'end_date',
        'optimization_goal',
        'bid_strategy',
        'bid_amount',
        'targeting_options',
        'placement_options',
        'meta',
    ];

    protected $casts = [
        'daily_budget' => 'decimal:2',
        'lifetime_budget' => 'decimal:2',
        'bid_amount' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'targeting_options' => 'array',
        'placement_options' => 'array',
        'meta' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(AdMetric::class, 'entity_id')
            ->where('entity_type', 'ad_set');
    }
}
