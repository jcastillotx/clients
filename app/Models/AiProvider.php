<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiProvider extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'ai_providers';

    protected $fillable = [
        'name',
        'api_key',
        'api_endpoint',
        'model_name',
        'status',
        'cost_per_1k_input_tokens',
        'cost_per_1k_output_tokens',
        'rate_limit_per_minute',
        'is_default',
        'priority_order',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'cost_per_1k_input_tokens' => 'decimal:6',
        'cost_per_1k_output_tokens' => 'decimal:6',
        'rate_limit_per_minute' => 'integer',
        'is_default' => 'boolean',
        'priority_order' => 'integer',
        'created_at' => 'datetime',
    ];
}
