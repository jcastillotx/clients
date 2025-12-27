<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTask extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'ai_tasks';

    protected $fillable = [
        'task_type',
        'input_data',
        'output_data',
        'provider_used',
        'model_used',
        'status',
        'tokens_used',
        'cost',
        'quality_rating',
        'quality_notes',
        'rated_by',
        'rated_at',
        'executed_by',
        'completed_at',
    ];

    protected $casts = [
        'input_data' => 'array',
        'output_data' => 'array',
        'tokens_used' => 'integer',
        'cost' => 'decimal:6',
        'quality_rating' => 'integer',
        'rated_by' => 'integer',
        'rated_at' => 'datetime',
        'executed_by' => 'integer',
        'created_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}
