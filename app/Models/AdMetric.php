<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdMetric extends Model
{
    protected $fillable = [
        'date',
        'entity_type',
        'entity_id',
        'client_id',
        'impressions',
        'clicks',
        'conversions',
        'spend',
        'revenue',
        'ctr',
        'cpc',
        'cpm',
        'cpa',
        'roas',
        'likes',
        'shares',
        'comments',
        'video_views',
        'video_view_rate',
        'platform_metrics',
    ];

    protected $casts = [
        'date' => 'date',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'spend' => 'decimal:2',
        'revenue' => 'decimal:2',
        'ctr' => 'decimal:4',
        'cpc' => 'decimal:2',
        'cpm' => 'decimal:2',
        'cpa' => 'decimal:2',
        'roas' => 'decimal:2',
        'likes' => 'integer',
        'shares' => 'integer',
        'comments' => 'integer',
        'video_views' => 'integer',
        'video_view_rate' => 'integer',
        'platform_metrics' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function entity()
    {
        return match ($this->entity_type) {
            'campaign' => $this->belongsTo(AdCampaign::class, 'entity_id'),
            'ad_set' => $this->belongsTo(AdSet::class, 'entity_id'),
            'ad' => $this->belongsTo(Ad::class, 'entity_id'),
            default => null,
        };
    }

    protected static function booted()
    {
        static::saving(function ($metric) {
            // Auto-calculate derived metrics
            if ($metric->impressions > 0) {
                $metric->ctr = ($metric->clicks / $metric->impressions) * 100;
            }

            if ($metric->clicks > 0) {
                $metric->cpc = $metric->spend / $metric->clicks;
            }

            if ($metric->impressions > 0) {
                $metric->cpm = ($metric->spend / $metric->impressions) * 1000;
            }

            if ($metric->conversions > 0) {
                $metric->cpa = $metric->spend / $metric->conversions;
            }

            if ($metric->spend > 0) {
                $metric->roas = $metric->revenue / $metric->spend;
            }
        });
    }
}
