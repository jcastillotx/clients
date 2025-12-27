<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignLink extends Model
{
    protected $fillable = [
        'campaign_id',
        'original_url',
        'short_url',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'clicks',
        'conversions',
        'meta',
    ];

    protected $casts = [
        'clicks' => 'integer',
        'conversions' => 'integer',
        'meta' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}

