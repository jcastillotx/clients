<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncedFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'storage_connection_id',
        'document_id',
        'request_id',
        'contract_id',
        'provider_file_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'last_modified_at',
        'synced_at',
        'sync_status',
        'tags',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'last_modified_at' => 'datetime',
        'synced_at' => 'datetime',
        'tags' => 'array',
    ];

    public function storageConnection(): BelongsTo
    {
        return $this->belongsTo(StorageConnection::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}

