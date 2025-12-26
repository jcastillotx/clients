<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'trigger',
        'trigger_meta',
        'conditions',
        'actions',
        'run_order',
        'last_ran_at',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'trigger_meta' => 'array',
        'conditions' => 'array',
        'actions' => 'array',
        'last_ran_at' => 'datetime',
        'run_order' => 'integer',
        'created_by' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AutomationLog::class)->orderByDesc('created_at')->orderByDesc('id');
    }
}

