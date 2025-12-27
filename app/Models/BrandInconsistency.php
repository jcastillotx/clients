<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandInconsistency extends Model
{
    protected $fillable = [
        'brand_audit_id',
        'category',
        'severity',
        'location',
        'description',
        'recommendation',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(BrandAudit::class, 'brand_audit_id');
    }
}

