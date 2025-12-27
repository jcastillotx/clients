<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBudget extends Model
{
    protected $fillable = [
        'request_id',
        'budget_hours',
        'budget_amount',
        'spent_hours',
        'spent_amount',
        'is_exceeded',
    ];

    protected $casts = [
        'budget_hours' => 'decimal:2',
        'budget_amount' => 'decimal:2',
        'spent_hours' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'is_exceeded' => 'boolean',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }
}

