<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingTask extends Model
{
    protected $fillable = [
        'onboarding_workflow_id',
        'task_name',
        'task_type',
        'assigned_to',
        'status',
        'due_date',
        'completed_at',
        'meta',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(OnboardingWorkflow::class, 'onboarding_workflow_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}

