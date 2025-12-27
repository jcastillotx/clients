<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorMonitoring extends Model
{
    protected $table = 'competitor_monitoring';

    protected $fillable = [
        'competitor_id',
        'monitored_at',
        'changes_detected',
        'alert_sent',
    ];

    protected $casts = [
        'monitored_at' => 'datetime',
        'changes_detected' => 'array',
        'alert_sent' => 'boolean',
    ];

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(BrandCompetitor::class, 'competitor_id');
    }
}
