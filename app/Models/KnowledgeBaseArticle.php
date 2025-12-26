<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeBaseArticle extends Model
{
    use HasFactory;

    protected $table = 'knowledge_base_articles';

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'video_url',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseCategory::class, 'category_id');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(KnowledgeBaseFeedback::class, 'article_id');
    }
}

