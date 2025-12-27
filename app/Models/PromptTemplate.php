<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromptTemplate extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'prompt_templates';

    protected $fillable = [
        'key',
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(PromptTemplateVersion::class, 'prompt_template_id')->orderByDesc('version');
    }
}
