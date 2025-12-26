<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnomalyAlert extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'anomaly_alerts';

    protected $fillable = [
        'type',
        'severity',
        'client_id',
        'title',
        'message',
        'data',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'data' => 'array',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}

