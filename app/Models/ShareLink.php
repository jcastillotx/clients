<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_type',
        'resource_id',
        'token',
        'permissions',
        'expires_at',
        'revoked_at',
        'last_accessed_at',
        'created_by',
    ];

    protected $casts = [
        'permissions' => 'array',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'created_by' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
