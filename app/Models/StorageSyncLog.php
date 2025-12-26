<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageSyncLog extends Model
{
    use HasFactory;

    protected $table = 'storage_sync_logs';

    protected $fillable = [
        'storage_connection_id',
        'status',
        'files_processed',
        'started_at',
        'finished_at',
        'message',
        'meta',
    ];

    protected $casts = [
        'files_processed' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'meta' => 'array',
    ];

    public function storageConnection(): BelongsTo
    {
        return $this->belongsTo(StorageConnection::class);
    }
}

