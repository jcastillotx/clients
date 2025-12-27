<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeywordRanking extends Model
{
    protected $fillable = [
        'seo_keyword_id',
        'position',
        'url_ranking',
        'search_engine',
        'location',
        'device',
        'tracked_at',
        'meta',
    ];

    protected $casts = [
        'position' => 'integer',
        'tracked_at' => 'datetime',
        'meta' => 'array',
    ];

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(SeoKeyword::class, 'seo_keyword_id');
    }
}

