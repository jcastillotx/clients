<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookEndpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'event_type',
        'webhook_url',
        'secret',
        'is_active',
        'format',
        'headers',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'headers' => 'array',
        'secret' => 'encrypted',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(WebhookDeliveryLog::class)->latest('id');
    }
}
