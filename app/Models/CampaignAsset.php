<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CampaignAsset extends Model
{
    protected $fillable = [
        'campaign_id',
        'asset_type',
        'asset_id',
        'is_primary',
        'meta',
    ];

    protected $casts = [
        'asset_id' => 'integer',
        'is_primary' => 'boolean',
        'meta' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function asset(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'asset_type', 'asset_id');
    }
}
