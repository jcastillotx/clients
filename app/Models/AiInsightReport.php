<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiInsightReport extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ai_insight_reports';

    protected $fillable = [
        'kind',
        'period_start',
        'period_end',
        'payload',
        'narrative',
        'provider_used',
        'model_used',
        'cost',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'payload' => 'array',
        'cost' => 'decimal:6',
        'created_at' => 'datetime',
    ];
}

