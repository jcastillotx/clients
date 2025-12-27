<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandAsset extends Model
{
    protected $fillable = [
        'client_id',
        'asset_type',
        'asset_name',
        'asset_value',
        'usage_context',
        'is_approved',
        'meta',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
