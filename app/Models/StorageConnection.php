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
        'auto_sync_enabled',
        'sync_frequency_minutes',
        'conflict_strategy',
        'last_synced_at',
        'quota_warned_80_at',
        'last_sync_failed_at',
        'sync_failed_notified_at',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'is_primary' => 'boolean',
        'auto_sync_enabled' => 'boolean',
        'storage_used' => 'integer',
        'storage_limit' => 'integer',
        'sync_frequency_minutes' => 'integer',
        'last_synced_at' => 'datetime',
        'quota_warned_80_at' => 'datetime',
        'last_sync_failed_at' => 'datetime',
        'sync_failed_notified_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function syncedFiles(): HasMany
    {
        return $this->hasMany(SyncedFile::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(StorageSyncLog::class, 'storage_connection_id')->orderByDesc('started_at')->orderByDesc('id');
    }
}

