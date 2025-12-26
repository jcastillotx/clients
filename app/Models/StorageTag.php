<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StorageTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'color',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(StorageFile::class, 'storage_file_tag', 'storage_tag_id', 'storage_file_id');
    }
}

