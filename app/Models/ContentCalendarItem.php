<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentCalendarItem extends Model
{
    protected $table = 'content_calendar';

    protected $fillable = [
        'client_id',
        'title',
        'content_type',
        'platform',
        'scheduled_for',
        'published_at',
        'status',
        'content_text',
        'media_urls',
        'hashtags',
        'campaign_tag',
        'approved_by',
        'created_by',
        'meta',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'published_at' => 'datetime',
        'media_urls' => 'array',
        'meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

