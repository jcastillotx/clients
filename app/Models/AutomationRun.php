<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'automation_rule_id',
        'trigger',
        'client_id',
        'context',
        'matched',
        'succeeded',
        'actions_total',
        'actions_succeeded',
        'actions_failed',
        'error',
        'ran_at',
    ];

    protected $casts = [
        'context' => 'array',
        'matched' => 'boolean',
        'succeeded' => 'boolean',
        'ran_at' => 'datetime',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }
}

