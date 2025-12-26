<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'ai_messages';

    protected $fillable = [
        'ai_conversation_id',
        'role',
        'content',
        'provider_used',
        'model_used',
        'tokens_used',
        'cost',
        'response_time_ms',
    ];

    protected $casts = [
        'ai_conversation_id' => 'integer',
        'tokens_used' => 'integer',
        'response_time_ms' => 'integer',
        'cost' => 'decimal:6',
        'created_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }
}

