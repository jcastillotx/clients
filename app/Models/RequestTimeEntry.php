<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestTimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'user_id',
        'hours',
        'note',
        'logged_at',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
        'logged_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

