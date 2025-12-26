<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageTracking extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'ai_usage_tracking';

    protected $fillable = [
        'client_id',
        'user_id',
        'provider',
        'model',
        'tokens_input',
        'tokens_output',
        'cost',
        'task_type',
    ];

    protected $casts = [
        'client_id' => 'integer',
        'user_id' => 'integer',
        'tokens_input' => 'integer',
        'tokens_output' => 'integer',
        'cost' => 'decimal:6',
        'created_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

