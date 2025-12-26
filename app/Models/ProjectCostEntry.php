<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectCostEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'description',
        'amount',
        'occurred_on',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'occurred_on' => 'date',
        'meta' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

