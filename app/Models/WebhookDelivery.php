<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_endpoint_id',
        'delivery_id',
        'event_type',
        'payload',
        'status',
        'attempts',
        'last_attempt_at',
        'next_attempt_at',
        'response_status',
        'response_body',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'last_attempt_at' => 'datetime',
        'next_attempt_at' => 'datetime',
        'response_status' => 'integer',
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }
}

