<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'storage_connection_id',
        'status',
        'started_at',
        'finished_at',
        'files_scanned',
        'files_added',
        'files_updated',
        'files_deleted',
        'conflicts',
        'error_message',
        'meta',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'meta' => 'array',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(StorageConnection::class, 'storage_connection_id');
    }
}
