<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'due_date',
        'completed_at',
        'sort_order',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
