<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndustryMonitor extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'industry_monitors';

    protected $fillable = [
        'title',
        'client_id',
        'created_by',
        'industry',
        'region',
        'keywords',
        'cadence',
        'status',
        'last_run_at',
        'last_report_id',
    ];

    protected $casts = [
        'keywords' => 'array',
        'last_run_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastReport(): BelongsTo
    {
        return $this->belongsTo(AiInsightReport::class, 'last_report_id');
    }
}

