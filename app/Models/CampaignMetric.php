<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignMetric extends Model
{
    protected $fillable = [
        'campaign_id',
        'metric_date',
        'channel',
        'impressions',
        'clicks',
        'conversions',
        'spend',
        'revenue',
        'roi',
        'meta',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'spend' => 'decimal:2',
        'revenue' => 'decimal:2',
        'roi' => 'decimal:2',
        'meta' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
