<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ad extends Model
{
    protected $fillable = [
        'ad_set_id',
        'ad_creative_id',
        'platform_ad_id',
        'name',
        'status',
        'headline',
        'description',
        'call_to_action',
        'destination_url',
        'display_url',
        'tracking_params',
        'disapproval_reason',
        'meta',
    ];

    protected $casts = [
        'tracking_params' => 'array',
        'meta' => 'array',
    ];

    public function adSet(): BelongsTo
    {
        return $this->belongsTo(AdSet::class);
    }

    public function creative(): BelongsTo
    {
        return $this->belongsTo(AdCreative::class, 'ad_creative_id');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(AdMetric::class, 'entity_id')
            ->where('entity_type', 'ad');
    }

    public function getFullDestinationUrlAttribute(): string
    {
        if (!$this->destination_url) {
            return '';
        }

        $url = $this->destination_url;

        if ($this->tracking_params) {
            $params = http_build_query($this->tracking_params);
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . $params;
        }

        return $url;
    }
}
