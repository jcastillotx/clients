<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalView extends Model
{
    protected $fillable = [
        'proposal_id',
        'viewed_at',
        'ip_address',
        'time_spent_seconds',
        'sections_viewed',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'time_spent_seconds' => 'integer',
        'sections_viewed' => 'array',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
