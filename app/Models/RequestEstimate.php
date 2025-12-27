<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestEstimate extends Model
{
    protected $fillable = [
        'request_id',
        'client_id',
        'created_by',
        'status',
        'ai_task_id',
        'estimate_data',
        'pricing_data',
        'client_selections',
        'sow_contract_id',
        'sent_at',
        'approved_at',
        'client_message',
    ];

    protected $casts = [
        'estimate_data' => 'array',
        'pricing_data' => 'array',
        'client_selections' => 'array',
        'sent_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sowContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'sow_contract_id');
    }

    public function aiTask(): BelongsTo
    {
        return $this->belongsTo(AiTask::class, 'ai_task_id');
    }
}
