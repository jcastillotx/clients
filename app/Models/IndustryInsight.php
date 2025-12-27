<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustryInsight extends Model
{
    protected $fillable = [
        'industry',
        'insight_type',
        'title',
        'content',
        'source_url',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];
}
