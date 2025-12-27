<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientStorageSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'auto_sync_enabled',
        'auto_sync_frequency',
        'conflict_rule',
        'quota_alert_percent',
        'backup_enabled',
        'backup_connection_id',
        'folders',
    ];

    protected $casts = [
        'auto_sync_enabled' => 'boolean',
        'backup_enabled' => 'boolean',
        'quota_alert_percent' => 'integer',
        'folders' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function backupConnection(): BelongsTo
    {
        return $this->belongsTo(StorageConnection::class, 'backup_connection_id');
    }
}
