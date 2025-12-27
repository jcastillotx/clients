<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_type',
        'source_id',
        'linkable_type',
        'linkable_id',
        'purpose',
    ];

    public function source(): MorphTo
    {
        return $this->morphTo('source');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo('linkable');
    }
}
