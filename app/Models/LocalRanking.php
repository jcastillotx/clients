<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocalRanking extends Model
{
    protected $fillable = [
        'client_id',
        'keyword',
        'business_name',
        'center_lat',
        'center_lng',
        'grid_size',
        'radius_miles',
        'grid_data',
        'average_position',
        'top_3_count',
        'visibility_score',
        'tracked_date',
        'tracked_at',
    ];

    protected $casts = [
        'center_lat' => 'decimal:6',
        'center_lng' => 'decimal:6',
        'grid_size' => 'integer',
        'radius_miles' => 'decimal:2',
        'grid_data' => 'array',
        'average_position' => 'decimal:1',
        'top_3_count' => 'integer',
        'visibility_score' => 'decimal:1',
        'tracked_date' => 'date',
        'tracked_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the grid data formatted for display
     */
    public function getGridMatrixAttribute(): array
    {
        $gridData = $this->grid_data ?? [];
        $size = $this->grid_size ?? 5;
        $matrix = [];

        foreach ($gridData as $point) {
            $row = $point['row'] ?? 0;
            $col = $point['col'] ?? 0;
            $matrix[$row][$col] = $point;
        }

        // Ensure complete matrix
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if (!isset($matrix[$r][$c])) {
                    $matrix[$r][$c] = ['row' => $r, 'col' => $c, 'business_position' => null];
                }
            }
        }

        return $matrix;
    }

    /**
     * Get color class based on position
     */
    public static function getPositionColorClass(?int $position): string
    {
        if ($position === null) {
            return 'bg-gray-200 text-gray-600';
        }

        return match (true) {
            $position === 1 => 'bg-green-500 text-white',
            $position === 2 => 'bg-green-400 text-white',
            $position === 3 => 'bg-green-300 text-gray-800',
            $position <= 5 => 'bg-yellow-300 text-gray-800',
            $position <= 10 => 'bg-orange-300 text-gray-800',
            $position <= 20 => 'bg-red-300 text-gray-800',
            default => 'bg-red-500 text-white',
        };
    }
}
