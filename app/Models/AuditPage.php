<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditPage extends Model
{
    protected $fillable = [
        'website_audit_id',
        'url',
        'title',
        'meta_description',
        'h1_tag',
        'word_count',
        'load_time_ms',
        'page_size_kb',
        'status_code',
        'has_canonical',
        'has_schema',
        'mobile_friendly',
        'headers',
        'links',
        'images',
    ];

    protected $casts = [
        'word_count' => 'integer',
        'load_time_ms' => 'integer',
        'page_size_kb' => 'integer',
        'status_code' => 'integer',
        'has_canonical' => 'boolean',
        'has_schema' => 'boolean',
        'mobile_friendly' => 'boolean',
        'headers' => 'array',
        'links' => 'array',
        'images' => 'array',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(WebsiteAudit::class, 'website_audit_id');
    }
}
