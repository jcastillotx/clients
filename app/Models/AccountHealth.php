<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountHealth extends Model
{
    protected $table = 'account_health';

    protected $fillable = [
        'client_id',
        'health_score',
        'engagement_score',
        'satisfaction_score',
        'revenue_trend',
        'growth_rate',
        'risk_factors',
        'opportunities',
        'calculated_at',
    ];

    protected $casts = [
        'health_score' => 'integer',
        'engagement_score' => 'integer',
        'satisfaction_score' => 'integer',
        'growth_rate' => 'decimal:2',
        'risk_factors' => 'array',
        'opportunities' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
