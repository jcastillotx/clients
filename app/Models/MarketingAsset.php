<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingAsset extends Model
{
    protected $fillable = [
        'client_id',
        'asset_name',
        'asset_type',
        'file_path',
        'file_size',
        'mime_type',
        'dimensions',
        'tags',
        'usage_rights',
        'expiration_date',
        'version',
        'is_latest',
        'created_by',
        'meta',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'tags' => 'array',
        'expiration_date' => 'date',
        'version' => 'integer',
        'is_latest' => 'boolean',
        'meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
