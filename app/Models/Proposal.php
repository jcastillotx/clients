<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proposal extends Model
{
    protected $fillable = [
        'client_id',
        'request_id',
        'contract_id',
        'invoice_id',
        'title',
        'proposal_number',
        'template_id',
        'content',
        'pricing_data',
        'status',
        'valid_until',
        'sent_at',
        'accepted_at',
        'signed_at',
        'signed_by',
        'signature_ip',
        'signature_data',
        'created_by',
    ];

    protected $casts = [
        'content' => 'array',
        'pricing_data' => 'array',
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'signed_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function views(): HasMany
    {
        return $this->hasMany(ProposalView::class);
    }

    public function selections(): HasMany
    {
        return $this->hasMany(ProposalSelection::class);
    }
}
