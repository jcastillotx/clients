<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandGuideSection extends Model
{
    protected $fillable = [
        'brand_guide_id',
        'section_type',
        'section_order',
        'title',
        'content',
        'is_visible',
    ];

    protected $casts = [
        'section_order' => 'integer',
        'content' => 'array',
        'is_visible' => 'boolean',
    ];

    public function guide(): BelongsTo
    {
        return $this->belongsTo(BrandGuide::class, 'brand_guide_id');
    }
}
