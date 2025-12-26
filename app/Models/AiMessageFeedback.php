<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessageFeedback extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ai_message_feedback';

    protected $fillable = [
        'ai_message_id',
        'user_id',
        'client_id',
        'rating',
        'helpful',
        'comment',
        'edited_text',
        'meta',
    ];

    protected $casts = [
        'helpful' => 'boolean',
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(AiMessage::class, 'ai_message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}

