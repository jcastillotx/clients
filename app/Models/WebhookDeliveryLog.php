<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDeliveryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_endpoint_id',
        'event_type',
        'payload',
        'attempt',
        'succeeded',
        'http_status',
        'duration_ms',
        'response_body',
        'error',
        'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'succeeded' => 'boolean',
        'delivered_at' => 'datetime',
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }
}
