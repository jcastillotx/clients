<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class RecurringInvoice extends Model
{
    use HasFactory, SoftDeletes;

    public const FREQUENCY_WEEKLY = 'weekly';

    public const FREQUENCY_BIWEEKLY = 'biweekly';

    public const FREQUENCY_MONTHLY = 'monthly';

    public const FREQUENCY_QUARTERLY = 'quarterly';

    public const FREQUENCY_YEARLY = 'yearly';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'client_id',
        'request_id',
        'contract_id',
        'frequency',
        'day_of_month',
        'day_of_week',
        'start_date',
        'end_date',
        'next_generate_date',
        'occurrences_limit',
        'occurrences_count',
        'name',
        'tax_rate',
        'discount',
        'notes',
        'terms',
        'template',
        'payment_terms_days',
        'line_items',
        'status',
        'auto_send',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_generate_date' => 'date',
        'tax_rate' => 'decimal:2',
        'discount' => 'decimal:2',
        'line_items' => 'array',
        'auto_send' => 'boolean',
        'occurrences_limit' => 'integer',
        'occurrences_count' => 'integer',
        'day_of_month' => 'integer',
        'day_of_week' => 'integer',
        'payment_terms_days' => 'integer',
    ];

    /**
     * Get frequency options for forms.
     */
    public static function frequencyOptions(): array
    {
        return [
            self::FREQUENCY_WEEKLY => 'Weekly',
            self::FREQUENCY_BIWEEKLY => 'Every 2 Weeks',
            self::FREQUENCY_MONTHLY => 'Monthly',
            self::FREQUENCY_QUARTERLY => 'Quarterly',
            self::FREQUENCY_YEARLY => 'Yearly',
        ];
    }

    /**
     * Get status options for forms.
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Get the client that owns this recurring invoice.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the request associated with this recurring invoice.
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    /**
     * Get the contract associated with this recurring invoice.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get all invoices generated from this recurring template.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Check if this recurring invoice is active and should generate.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if this recurring invoice has reached its limit.
     */
    public function hasReachedLimit(): bool
    {
        if ($this->occurrences_limit === null) {
            return false;
        }

        return $this->occurrences_count >= $this->occurrences_limit;
    }

    /**
     * Check if the end date has passed.
     */
    public function hasExpired(): bool
    {
        if ($this->end_date === null) {
            return false;
        }

        return $this->end_date->isPast();
    }

    /**
     * Check if invoice should be generated today.
     */
    public function shouldGenerateToday(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if ($this->hasReachedLimit() || $this->hasExpired()) {
            return false;
        }

        if ($this->next_generate_date === null) {
            return false;
        }

        return $this->next_generate_date->isToday() || $this->next_generate_date->isPast();
    }

    /**
     * Calculate the next generation date based on frequency.
     */
    public function calculateNextGenerateDate(?Carbon $from = null): Carbon
    {
        $from = $from ?? ($this->next_generate_date ?? Carbon::parse($this->start_date));

        return match ($this->frequency) {
            self::FREQUENCY_WEEKLY => $from->copy()->addWeek(),
            self::FREQUENCY_BIWEEKLY => $from->copy()->addWeeks(2),
            self::FREQUENCY_MONTHLY => $this->calculateNextMonthlyDate($from),
            self::FREQUENCY_QUARTERLY => $this->calculateNextMonthlyDate($from, 3),
            self::FREQUENCY_YEARLY => $this->calculateNextMonthlyDate($from, 12),
            default => $from->copy()->addMonth(),
        };
    }

    /**
     * Calculate next monthly/quarterly/yearly date respecting day_of_month.
     */
    protected function calculateNextMonthlyDate(Carbon $from, int $months = 1): Carbon
    {
        $next = $from->copy()->addMonths($months);

        if ($this->day_of_month) {
            $maxDay = $next->daysInMonth;
            $day = min($this->day_of_month, $maxDay);
            $next->setDay($day);
        }

        return $next;
    }

    /**
     * Advance to the next occurrence after generating an invoice.
     */
    public function advanceToNextOccurrence(): void
    {
        $this->occurrences_count++;
        $this->next_generate_date = $this->calculateNextGenerateDate();

        // Check if we've reached the limit or end date
        if ($this->hasReachedLimit() || $this->hasExpired()) {
            $this->status = self::STATUS_COMPLETED;
        }

        $this->save();
    }

    /**
     * Calculate the subtotal from line items.
     */
    public function getSubtotalAttribute(): float
    {
        $items = $this->line_items ?? [];

        return round(array_sum(array_map(function ($item) {
            return (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
        }, $items)), 2);
    }

    /**
     * Calculate the tax amount.
     */
    public function getTaxAmountAttribute(): float
    {
        return round($this->subtotal * ((float) $this->tax_rate / 100), 2);
    }

    /**
     * Calculate the total.
     */
    public function getTotalAttribute(): float
    {
        return max(0, round($this->subtotal + $this->tax_amount - (float) $this->discount, 2));
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_PAUSED => 'warning',
            self::STATUS_COMPLETED => 'info',
            self::STATUS_CANCELLED => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get frequency label.
     */
    public function getFrequencyLabelAttribute(): string
    {
        return self::frequencyOptions()[$this->frequency] ?? ucfirst($this->frequency);
    }

    /**
     * Scope for active recurring invoices due for generation.
     */
    public function scopeDueForGeneration($query)
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where('next_generate_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('occurrences_limit')
                    ->orWhereColumn('occurrences_count', '<', 'occurrences_limit');
            });
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (RecurringInvoice $recurring) {
            // Set initial next_generate_date if not set
            if (! $recurring->next_generate_date) {
                $recurring->next_generate_date = $recurring->start_date;
            }

            // Default template
            if (! $recurring->template) {
                $recurring->template = 'classic';
            }
        });
    }
}
