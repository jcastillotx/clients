<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'automation_rule_id',
        'trigger',
        'status',
        'message',
        'context',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'automation_rule_id' => 'integer',
        'context' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }
}

