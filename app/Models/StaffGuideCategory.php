<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffGuideCategory extends Model
{
    use HasFactory;

    protected $table = 'staff_guide_categories';

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function guides(): HasMany
    {
        return $this->hasMany(StaffGuide::class, 'category_id');
    }

    public function publishedGuides(): HasMany
    {
        return $this->hasMany(StaffGuide::class, 'category_id')
            ->where('is_published', true)
            ->orderBy('sort_order');
    }
}
