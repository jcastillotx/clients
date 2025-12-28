<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditHistory extends Model
{
    protected $table = 'audit_history';

    protected $fillable = [
        'client_id',
        'website_url',
        'website_url_hash',
        'audit_date',
        'overall_score',
        'seo_score',
        'performance_score',
        'accessibility_score',
        'total_issues',
        'critical_issues',
        'pages_crawled',
    ];

    protected static function booted(): void
    {
        static::saving(function (AuditHistory $history) {
            $history->website_url_hash = hash('sha256', $history->website_url);
        });
    }

    protected $casts = [
        'audit_date' => 'date',
        'overall_score' => 'integer',
        'seo_score' => 'integer',
        'performance_score' => 'integer',
        'accessibility_score' => 'integer',
        'total_issues' => 'integer',
        'critical_issues' => 'integer',
        'pages_crawled' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
