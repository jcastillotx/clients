<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandTemplate extends Model
{
    protected $fillable = [
        'brand_guide_id',
        'template_name',
        'template_type',
        'file_path',
        'thumbnail',
        'download_count',
        'is_public',
        'meta',
    ];

    protected $casts = [
        'download_count' => 'integer',
        'is_public' => 'boolean',
        'meta' => 'array',
    ];

    public function guide(): BelongsTo
    {
        return $this->belongsTo(BrandGuide::class, 'brand_guide_id');
    }
}

