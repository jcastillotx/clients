<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiComplianceLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ai_compliance_logs';

    protected $fillable = [
        'ai_task_id',
        'ai_conversation_id',
        'ai_message_id',
        'client_id',
        'user_id',
        'task_type',
        'provider',
        'model',
        'input_hash',
        'input_redacted',
        'output_preview',
        'pii_detected',
        'flagged_for_review',
        'flags',
        'retention_until',
        'deleted_at',
    ];

    protected $casts = [
        'pii_detected' => 'boolean',
        'flagged_for_review' => 'boolean',
        'flags' => 'array',
        'retention_until' => 'datetime',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(AiTask::class, 'ai_task_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(AiMessage::class, 'ai_message_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
