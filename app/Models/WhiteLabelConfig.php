<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhiteLabelConfig extends Model
{
    protected $fillable = [
        'client_id',
        'custom_domain',
        'logo_url',
        'primary_color',
        'secondary_color',
        'font_family',
        'company_name',
        'footer_text',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
