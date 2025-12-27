<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'progress_percent',
        'budget_amount',
        'currency',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'progress_percent' => 'integer',
        'budget_amount' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('sort_order');
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(ProjectDeliverable::class)->orderBy('sort_order');
    }

    public function costEntries(): HasMany
    {
        return $this->hasMany(ProjectCostEntry::class)->latest('id');
    }

    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_team_members')
            ->withPivot(['role', 'hourly_rate'])
            ->withTimestamps();
    }

    public function getCalculatedProgressPercentAttribute(): int
    {
        if ($this->progress_percent !== null) {
            return max(0, min(100, (int) $this->progress_percent));
        }

        $total = $this->deliverables()->count();
        if ($total <= 0) {
            return 0;
        }
        $done = $this->deliverables()->where('is_done', true)->count();

        return (int) floor(($done / $total) * 100);
    }

    public function getActualSpendAttribute(): float
    {
        return (float) $this->costEntries()->sum('amount');
    }
}
