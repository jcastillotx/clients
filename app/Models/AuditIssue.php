<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditIssue extends Model
{
    protected $fillable = [
        'website_audit_id',
        'severity',
        'category',
        'issue_type',
        'description',
        'affected_url',
        'recommendation',
        'is_resolved',
        'resolved_at',
        'priority_score',
        'meta',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'priority_score' => 'integer',
        'meta' => 'array',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(WebsiteAudit::class, 'website_audit_id');
    }
}
