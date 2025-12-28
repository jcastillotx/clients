<?php

namespace App\Models;

use App\Models\Concerns\LogsActivityWithContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;
    use LogsActivityWithContext;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'request_id',
        'contract_id',
        'recurring_invoice_id',
        'invoice_number',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount',
        'amount',
        'issue_date',
        'due_date',
        'paid_at',
        'reminded_due_7_at',
        'reminded_overdue_3_at',
        'status',
        'notes',
        'terms',
        'pdf_path',
        'template',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'amount' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'reminded_due_7_at' => 'datetime',
        'reminded_overdue_3_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'issue_date',
        'due_date',
        'paid_at',
        'reminded_due_7_at',
        'reminded_overdue_3_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('invoices')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
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
     * Get the contract associated with the invoice (optional).
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the recurring invoice template this invoice was generated from.
     */
    public function recurringInvoice(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoice::class);
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
        if (! $this->pdf_path) {
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
     * Total paid (successful payments).
     */
    public function getTotalPaidAttribute(): float
    {
        // If query included a precomputed alias (recommended for lists), use it.
        $attrs = $this->getAttributes();
        if (array_key_exists('total_paid', $attrs)) {
            return (float) $attrs['total_paid'];
        }

        // Fallback: cache the aggregate for 15 minutes.
        return (float) Cache::remember(
            "invoice:{$this->id}:total_paid",
            now()->addMinutes(15),
            fn () => (float) $this->payments()->where('status', 'succeeded')->sum('amount')
        );
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
        return max(0, (float) $this->amount - $this->total_paid);
    }

    /**
     * Attribute accessor for "is_overdue".
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->isOverdue();
    }

    /**
     * Get days until due.
     */
    public function getDaysUntilDueAttribute(): ?int
    {
        if (! $this->due_date) {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Calculate totals from items.
     */
    public function calculateTotal(): float
    {
        $subtotal = $this->items()->sum('total');
        $taxAmount = $subtotal * ($this->tax_rate / 100);
        $amount = $subtotal + $taxAmount - $this->discount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'amount' => max(0, $amount),
        ]);

        return (float) $this->amount;
    }

    /**
     * Backwards-compatible alias.
     */
    public function calculateTotals(): void
    {
        $this->calculateTotal();
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
            if (! $invoice->invoice_number) {
                $invoice->invoice_number = static::generateInvoiceNumber();
            }
            if (! $invoice->issue_date) {
                $invoice->issue_date = now();
            }
            if (! $invoice->due_date) {
                $invoice->due_date = now()->addDays(30);
            }
            if (! $invoice->template) {
                $invoice->template = 'classic';
            }
        });
    }
}
