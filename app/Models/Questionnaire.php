<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Questionnaire extends Model
{
    protected $fillable = [
        'client_id',
        'questionnaire_type',
        'title',
        'questions',
        'answers',
        'status',
        'assigned_to',
        'due_date',
        'sent_at',
        'submitted_at',
    ];

    protected $casts = [
        'questions' => 'array',
        'answers' => 'array',
        'due_date' => 'date',
        'sent_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
