<?php

namespace App\Models;

use App\Models\Concerns\LogsActivityWithContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    use HasFactory;
    use LogsActivity;
    use LogsActivityWithContext;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_id',
        'client_id',
        'amount',
        'payment_method',
        'transaction_id',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'status',
        'failure_reason',
        'metadata',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'processed_at',
        'created_at',
        'updated_at',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('payments')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the invoice for this payment.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the client for this payment.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Check if payment succeeded.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'succeeded';
    }

    /**
     * Check if payment failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'processing' => 'info',
            'succeeded' => 'success',
            'failed' => 'danger',
            'refunded' => 'secondary',
            'cancelled' => 'dark',
            default => 'secondary',
        };
    }

    /**
     * Mark payment as successful.
     */
    public function markAsSuccessful(string $transactionId, ?string $chargeId = null): void
    {
        $this->update([
            'status' => 'succeeded',
            'transaction_id' => $transactionId,
            'stripe_charge_id' => $chargeId,
            'processed_at' => now(),
        ]);

        // Check if invoice is fully paid
        if ($this->invoice->balance_due <= 0) {
            $this->invoice->markAsPaid();
        }
    }

    /**
     * Mark payment as failed.
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'processed_at' => now(),
        ]);
    }

    /**
     * Scope for successful payments.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'succeeded');
    }

    /**
     * Scope for failed payments.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    protected static function booted(): void
    {
        static::saved(function (Payment $payment) {
            Cache::forget("invoice:{$payment->invoice_id}:total_paid");
        });

        static::deleted(function (Payment $payment) {
            Cache::forget("invoice:{$payment->invoice_id}:total_paid");
        });
    }
}
