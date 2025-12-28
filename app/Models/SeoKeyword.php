<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoKeyword extends Model
{
    protected $fillable = [
        'client_id',
        'website_url',
        'website_url_hash',
        'keyword',
        'search_volume',
        'difficulty',
        'cpc',
        'current_position',
        'target_position',
        'tracking_enabled',
        'meta',
    ];

    protected static function booted(): void
    {
        static::saving(function (SeoKeyword $keyword) {
            $keyword->website_url_hash = hash('sha256', $keyword->website_url);
        });
    }

    protected $casts = [
        'search_volume' => 'integer',
        'difficulty' => 'integer',
        'cpc' => 'decimal:2',
        'current_position' => 'integer',
        'target_position' => 'integer',
        'tracking_enabled' => 'boolean',
        'meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(KeywordRanking::class, 'seo_keyword_id');
    }
}
