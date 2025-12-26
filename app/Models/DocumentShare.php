<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_type',
        'source_id',
        'token',
        'expires_at',
        'max_downloads',
        'downloads',
        'permissions',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'permissions' => 'array',
    ];

    public function source(): MorphTo
    {
        return $this->morphTo('source');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        if ($this->expires_at && $this->expires_at->isPast()) {
            return true;
        }
        if ($this->max_downloads !== null && $this->downloads >= $this->max_downloads) {
            return true;
        }
        return false;
    }
}

