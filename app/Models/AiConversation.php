<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConversation extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $table = 'ai_conversations';

    protected $fillable = [
        'client_id',
        'user_id',
        'context_type',
        'context_id',
        'title',
    ];

    protected $casts = [
        'client_id' => 'integer',
        'user_id' => 'integer',
        'context_id' => 'integer',
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

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'ai_conversation_id')->orderBy('id');
    }
}
