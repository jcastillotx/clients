<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BrandGuide extends Model
{
    protected $fillable = [
        'client_id',
        'version',
        'status',
        'cover_image',
        'slug',
        'is_public',
        'password_protected',
        'password',
        'created_by',
        'published_at',
        'meta',
    ];

    protected $casts = [
        'version' => 'integer',
        'is_public' => 'boolean',
        'password_protected' => 'boolean',
        'published_at' => 'datetime',
        'meta' => 'array',
    ];

    protected $hidden = [
        'password',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(BrandGuideSection::class);
    }

    public function colors(): HasMany
    {
        return $this->hasMany(BrandColor::class);
    }

    public function fonts(): HasMany
    {
        return $this->hasMany(BrandFont::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(BrandTemplate::class);
    }
}

