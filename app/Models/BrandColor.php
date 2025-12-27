<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandColor extends Model
{
    protected $fillable = [
        'brand_guide_id',
        'color_name',
        'color_type',
        'hex_value',
        'rgb_value',
        'cmyk_value',
        'pantone_value',
        'usage_context',
        'accessibility_notes',
    ];

    public function guide(): BelongsTo
    {
        return $this->belongsTo(BrandGuide::class, 'brand_guide_id');
    }
}

