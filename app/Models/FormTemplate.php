<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormTemplate extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'fields',
        'baseline_fields',
        'is_active',
    ];

    protected $casts = [
        'fields' => 'array',
        'baseline_fields' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Check if a field key is a baseline field (cannot be deleted).
     */
    public function isBaselineField(string $key): bool
    {
        return in_array($key, $this->baseline_fields ?? [], true);
    }

    /**
     * Get field by key.
     */
    public function getField(string $key): ?array
    {
        foreach ($this->fields ?? [] as $field) {
            if (($field['key'] ?? null) === $key) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Scope to find by slug.
     */
    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    /**
     * Scope to get active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
