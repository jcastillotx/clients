<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalSelection extends Model
{
    protected $fillable = [
        'proposal_id',
        'selected_tier',
        'selected_addons',
        'total_amount',
    ];

    protected $casts = [
        'selected_addons' => 'array',
        'total_amount' => 'decimal:2',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
