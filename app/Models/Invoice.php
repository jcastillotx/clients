<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'request_id',
        'invoice_number',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount',
        'amount',
        'issue_date',
        'due_date',
        'paid_at',
        'status',
        'notes',
        'terms',
        'pdf_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'amount' => 'decimal:2',
            'issue_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Get the client that owns the invoice.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the request associated with the invoice.
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    /**
     * Get the invoice items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    /**
     * Get payments for this invoice.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the PDF URL.
     */
    public function getPdfUrlAttribute(): ?string
    {
        if (!$this->pdf_path) {
            return null;
        }

        return Storage::disk('invoices')->url($this->pdf_path);
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->isPaid()) {
            return false;
        }

        return $this->due_date && $this->due_date->isPast();
    }

    /**
     * Check if invoice can be paid.
     */
    public function canBePaid(): bool
    {
        return in_array($this->status, ['sent', 'overdue']);
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return config("client-portal.invoice_statuses.{$this->status}", ucfirst($this->status));
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'sent' => 'info',
            'paid' => 'success',
            'overdue' => 'danger',
            'cancelled' => 'dark',
            'refunded' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Get balance due.
     */
    public function getBalanceDueAttribute(): float
    {
        $paid = $this->payments()->where('status', 'succeeded')->sum('amount');
        return max(0, $this->amount - $paid);
    }

    /**
     * Get days until due.
     */
    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Calculate totals from items.
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->items()->sum('total');
        $taxAmount = $subtotal * ($this->tax_rate / 100);
        $amount = $subtotal + $taxAmount - $this->discount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'amount' => max(0, $amount),
        ]);
    }

    /**
     * Mark as paid.
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark as sent.
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
        ]);
    }

    /**
     * Check and update overdue status.
     */
    public function checkOverdue(): void
    {
        if ($this->status === 'sent' && $this->isOverdue()) {
            $this->update(['status' => 'overdue']);
        }
    }

    /**
     * Scope for unpaid invoices.
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['sent', 'overdue']);
    }

    /**
     * Scope for overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    /**
     * Generate a unique invoice number.
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = config('client-portal.invoice.prefix', 'INV-');
        $year = now()->format('Y');
        $month = now()->format('m');
        
        $lastInvoice = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInvoice ? (int) substr($lastInvoice->invoice_number, -4) + 1 : 1;

        return sprintf('%s%s%s-%04d', $prefix, $year, $month, $sequence);
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (!$invoice->invoice_number) {
                $invoice->invoice_number = static::generateInvoiceNumber();
            }
            if (!$invoice->issue_date) {
                $invoice->issue_date = now();
            }
            if (!$invoice->due_date) {
                $invoice->due_date = now()->addDays(30);
            }
        });
    }
}
