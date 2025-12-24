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
        'quantity',
        'unit_price',
        'total',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

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
