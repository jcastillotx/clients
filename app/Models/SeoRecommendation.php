<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoRecommendation extends Model
{
    protected $fillable = [
        'website_audit_id',
        'category',
        'priority',
        'title',
        'description',
        'impact_estimate',
        'implementation_effort',
        'status',
        'assigned_to',
        'completed_at',
        'meta',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(WebsiteAudit::class, 'website_audit_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
