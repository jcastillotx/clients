<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingWorkflow extends Model
{
    protected $fillable = [
        'client_id',
        'status',
        'current_step',
        'total_steps',
        'completion_percentage',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'current_step' => 'integer',
        'total_steps' => 'integer',
        'completion_percentage' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(OnboardingTask::class)->orderBy('id');
    }

    public function recalcProgress(): void
    {
        $total = (int) $this->tasks()->count();
        $done = (int) $this->tasks()->where('status', 'completed')->count();

        $pct = $total > 0 ? (int) floor(($done / $total) * 100) : 0;
        $this->update([
            'total_steps' => max(1, $total),
            'completion_percentage' => max(0, min(100, $pct)),
            'status' => $total > 0 && $done >= $total ? 'completed' : 'in_progress',
            'completed_at' => $total > 0 && $done >= $total ? now() : null,
            'started_at' => $this->started_at ?: now(),
        ]);
    }
}

