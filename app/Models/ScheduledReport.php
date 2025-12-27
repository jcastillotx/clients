<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledReport extends Model
{
    protected $fillable = [
        'client_id',
        'report_name',
        'report_type',
        'frequency',
        'recipients',
        'next_run_at',
        'last_run_at',
        'is_active',
        'last_error',
        'template',
    ];

    protected $casts = [
        'recipients' => 'array',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'is_active' => 'boolean',
        'template' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
