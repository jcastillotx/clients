<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageSyncConflict extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'filename',
        'candidates',
        'chosen',
        'resolution',
        'notes',
    ];

    protected $casts = [
        'candidates' => 'array',
        'chosen' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
