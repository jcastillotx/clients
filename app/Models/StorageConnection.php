<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'provider',
        'credentials',
        'status',
        'storage_used',
        'storage_limit',
        'is_primary',
        'last_synced_at',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'is_primary' => 'boolean',
        'storage_used' => 'integer',
        'storage_limit' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function syncedFiles(): HasMany
    {
        return $this->hasMany(SyncedFile::class);
    }
}

