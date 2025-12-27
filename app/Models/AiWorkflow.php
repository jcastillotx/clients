<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiWorkflow extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ai_workflows';

    protected $fillable = [
        'name',
        'status',
        'definition',
        'created_by',
    ];

    protected $casts = [
        'definition' => 'array',
        'created_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
