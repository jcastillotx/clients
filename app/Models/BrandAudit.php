<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BrandAudit extends Model
{
    protected $fillable = [
        'client_id',
        'audit_date',
        'status',
        'overall_score',
        'visual_score',
        'messaging_score',
        'consistency_score',
        'perception_score',
        'report',
        'failure_reason',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'overall_score' => 'integer',
        'visual_score' => 'integer',
        'messaging_score' => 'integer',
        'consistency_score' => 'integer',
        'perception_score' => 'integer',
        'report' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function inconsistencies(): HasMany
    {
        return $this->hasMany(BrandInconsistency::class);
    }
}

