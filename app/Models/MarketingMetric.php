<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingMetric extends Model
{
    protected $fillable = [
        'client_id',
        'metric_date',
        'metric_type',
        'source',
        'metric_name',
        'metric_value',
        'metric_value_text',
        'meta',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'metric_value' => 'decimal:4',
        'meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
