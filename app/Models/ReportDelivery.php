<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ReportDelivery extends Model
{
    protected $fillable = [
        'report_schedule_id',
        'report_template_id',
        'client_id',
        'category',
        'meta',
        'disk',
        'path',
        'recipients',
        'generated_at',
        'sent_at',
        'status',
        'error',
    ];

    protected $casts = [
        'meta' => 'array',
        'recipients' => 'array',
        'generated_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ReportSchedule::class, 'report_schedule_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getDownloadUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}

