<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_template_id',
        'created_by',
        'frequency',
        'recipients',
        'is_active',
        'next_run_at',
        'last_run_at',
        'last_error',
    ];

    protected $casts = [
        'recipients' => 'array',
        'is_active' => 'boolean',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

