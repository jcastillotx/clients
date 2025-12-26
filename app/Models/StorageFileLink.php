<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StorageFileLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'storage_file_id',
        'linkable_type',
        'linkable_id',
        'purpose',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(StorageFile::class, 'storage_file_id');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
}

