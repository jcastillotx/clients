<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteAuditSchedule extends Model
{
    protected $fillable = [
        'client_id',
        'website_url',
        'website_url_hash',
        'audit_type',
        'frequency',
        'is_active',
        'max_pages',
        'competitors',
        'recipients',
        'last_run_at',
        'next_run_at',
        'last_error',
    ];

    protected static function booted(): void
    {
        static::saving(function (WebsiteAuditSchedule $schedule) {
            $schedule->website_url_hash = hash('sha256', $schedule->website_url);
        });
    }

    protected $casts = [
        'is_active' => 'boolean',
        'max_pages' => 'integer',
        'competitors' => 'array',
        'recipients' => 'array',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
