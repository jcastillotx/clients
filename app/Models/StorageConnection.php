<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

class StorageConnection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'provider',
        'name',
        'disk',
        'status',
        'is_primary',
        'used_bytes',
        'quota_bytes',
        'last_sync_at',
        'last_error',
        'settings',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'used_bytes' => 'integer',
        'quota_bytes' => 'integer',
        'last_sync_at' => 'datetime',
        'settings' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(StorageFile::class, 'storage_connection_id');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(StorageSyncLog::class, 'storage_connection_id')->latest('id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Active',
            'error' => 'Error',
            default => 'Disconnected',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'error' => 'danger',
            default => 'secondary',
        };
    }

    public function getProviderIconClassAttribute(): string
    {
        return match ($this->provider) {
            's3' => 'fas fa-cloud',
            'dropbox' => 'fab fa-dropbox',
            'drive' => 'fab fa-google-drive',
            default => 'fas fa-hdd',
        };
    }

    public function getQuotaPercentAttribute(): ?float
    {
        if (! $this->quota_bytes || $this->quota_bytes <= 0) {
            return null;
        }

        return min(100, round(($this->used_bytes / $this->quota_bytes) * 100, 2));
    }

    public function foldersToSync(): array
    {
        $folders = Arr::get($this->settings, 'folders', []);
        $folders = is_string($folders) ? array_filter(array_map('trim', explode(',', $folders))) : (array) $folders;

        return array_values(array_filter($folders, fn ($f) => $f !== null && $f !== ''));
    }
}
