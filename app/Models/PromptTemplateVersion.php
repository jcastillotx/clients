<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptTemplateVersion extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'prompt_template_versions';

    protected $fillable = [
        'prompt_template_id',
        'version',
        'status',
        'system_prompt',
        'variables',
        'notes',
    ];

    protected $casts = [
        'version' => 'integer',
        'variables' => 'array',
        'created_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(PromptTemplate::class, 'prompt_template_id');
    }
}
