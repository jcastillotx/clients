<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffGuideView extends Model
{
    use HasFactory;

    protected $table = 'staff_guide_views';

    protected $fillable = [
        'guide_id',
        'user_id',
    ];

    public function guide(): BelongsTo
    {
        return $this->belongsTo(StaffGuide::class, 'guide_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
