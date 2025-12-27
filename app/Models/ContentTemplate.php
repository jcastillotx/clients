<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentTemplate extends Model
{
    protected $fillable = [
        'client_id',
        'template_name',
        'template_type',
        'content',
        'variables',
        'usage_count',
        'meta',
    ];

    protected $casts = [
        'variables' => 'array',
        'usage_count' => 'integer',
        'meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
