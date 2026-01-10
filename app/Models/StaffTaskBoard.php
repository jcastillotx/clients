<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffTaskBoard extends Model
{
    protected $fillable = [
        'name',
        'description',
        'created_by',
        'color',
        'is_default',
        'is_archived',
        'settings',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_archived' => 'boolean',
        'settings' => 'array',
        'sort_order' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(StaffTaskColumn::class, 'board_id')->orderBy('sort_order');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(StaffTask::class, 'board_id');
    }

    public function labels(): HasMany
    {
        return $this->hasMany(StaffTaskLabel::class, 'board_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($board) {
            // Create default columns for new boards
            $defaultColumns = [
                ['name' => 'To Do', 'color' => '#94a3b8', 'icon' => 'fa-list', 'sort_order' => 0],
                ['name' => 'In Progress', 'color' => '#3b82f6', 'icon' => 'fa-spinner', 'sort_order' => 1],
                ['name' => 'Review', 'color' => '#f59e0b', 'icon' => 'fa-eye', 'sort_order' => 2],
                ['name' => 'Done', 'color' => '#10b981', 'icon' => 'fa-check', 'sort_order' => 3, 'is_done_column' => true],
            ];

            foreach ($defaultColumns as $column) {
                $board->columns()->create($column);
            }
        });
    }
}
