<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientReportConfig extends Model
{
    protected $fillable = [
        'client_id',
        'visible_metrics',
        'report_frequency',
        'delivery_method',
        'recipients',
        'custom_branding',
    ];

    protected $casts = [
        'visible_metrics' => 'array',
        'recipients' => 'array',
        'custom_branding' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
