<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebsiteAudit extends Model
{
    protected $fillable = [
        'client_id',
        'website_url',
        'website_url_hash',
        'audit_type',
        'status',
        'score',
        'started_at',
        'completed_at',
        'report',
        'scores',
        'meta',
        'failure_reason',
    ];

    protected static function booted(): void
    {
        static::saving(function (WebsiteAudit $audit) {
            $audit->website_url_hash = hash('sha256', $audit->website_url);
        });
    }

    protected $casts = [
        'score' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'report' => 'array',
        'scores' => 'array',
        'meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(AuditIssue::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(AuditPage::class);
    }
}
