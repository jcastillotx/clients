<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_id',
        'description',
        'feature_key',
        'quantity',
        'unit_price',
        'total',
        'sort_order',
    ];

    protected $casts = [
        'feature_key' => 'string',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Get the invoice that owns the item.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Calculate and set the total.
     */
    public function calculateTotal(): void
    {
        $this->total = $this->quantity * $this->unit_price;
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::saving(function (InvoiceItem $item) {
            $item->calculateTotal();
        });

        static::saved(function (InvoiceItem $item) {
            $item->invoice->calculateTotals();
        });

        static::deleted(function (InvoiceItem $item) {
            $item->invoice->calculateTotals();
        });
    }
}
