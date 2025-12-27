<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomDashboard extends Model
{
    protected $fillable = [
        'client_id',
        'dashboard_name',
        'configuration',
        'is_default',
        'created_by',
        'meta',
    ];

    protected $casts = [
        'configuration' => 'array',
        'is_default' => 'boolean',
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
