<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeBaseFeedback extends Model
{
    use HasFactory;

    protected $table = 'knowledge_base_feedback';

    protected $fillable = [
        'article_id',
        'user_id',
        'was_helpful',
        'comment',
    ];

    protected $casts = [
        'was_helpful' => 'boolean',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseArticle::class, 'article_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

