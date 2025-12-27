<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientAiQuestion extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'client_ai_questions';

    protected $fillable = [
        'client_id',
        'asked_by',
        'ai_conversation_id',
        'category',
        'topic',
        'question',
        'answer',
        'sources',
        'tags',
        'is_opportunity',
        'opportunity_type',
        'request_id',
        'answered_at',
    ];

    protected $casts = [
        'sources' => 'array',
        'tags' => 'array',
        'is_opportunity' => 'boolean',
        'answered_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function asker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asked_by');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class, 'request_id');
    }
}
