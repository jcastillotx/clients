<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientHealthSnapshot extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'client_health_snapshots';

    protected $fillable = [
        'client_id',
        'score',
        'churn_probability',
        'risk_level',
        'breakdown',
        'computed_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'churn_probability' => 'decimal:4',
        'breakdown' => 'array',
        'computed_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}

