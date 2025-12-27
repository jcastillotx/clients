<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = [
        'name',
        'email',
        'code',
        'commission_rate',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }
}

