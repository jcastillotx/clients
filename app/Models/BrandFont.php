<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandFont extends Model
{
    protected $fillable = [
        'brand_guide_id',
        'font_name',
        'font_category',
        'font_weights',
        'font_file_path',
        'web_font_url',
        'usage_context',
        'licensing_info',
    ];

    protected $casts = [
        'font_weights' => 'array',
    ];

    public function guide(): BelongsTo
    {
        return $this->belongsTo(BrandGuide::class, 'brand_guide_id');
    }
}
