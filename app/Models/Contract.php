<?php

namespace App\Models;

use App\Models\Concerns\LogsActivityWithContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Contract extends Model
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
        'title',
        'description',
        'file_path',
        'contract_number',
        'start_date',
        'end_date',
        'value',
        'status',
        'signed_at',
        'signed_by',
        'signature_ip',
        'signature_data',
        'meta',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'value' => 'decimal:2',
        'signed_at' => 'datetime',
        'deleted_at' => 'datetime',
        'meta' => 'array',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'start_date',
        'end_date',
        'signed_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('contracts')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the client that owns the contract.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the file URL.
     */
    public function getFileUrlAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return Storage::disk('contracts')->url($this->file_path);
    }

    /**
     * Check if contract is signed.
     */
    public function isSigned(): bool
    {
        return $this->signed_at !== null;
    }

    /**
     * Check if contract is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if contract is expired.
     */
    public function isExpired(): bool
    {
        if (! $this->end_date) {
            return false;
        }

        return $this->end_date->isPast();
    }

    /**
     * Days until expiration (negative if already expired).
     */
    public function daysUntilExpiration(): ?int
    {
        return $this->end_date ? now()->diffInDays($this->end_date, false) : null;
    }

    /**
     * Check if contract is pending signature.
     */
    public function isPendingSignature(): bool
    {
        return $this->status === 'pending_signature';
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return config("client-portal.contract_statuses.{$this->status}", ucfirst(str_replace('_', ' ', $this->status)));
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'pending_signature' => 'warning',
            'active' => 'success',
            'expired' => 'danger',
            'terminated' => 'dark',
            default => 'secondary',
        };
    }

    /**
     * Get days until expiration.
     */
    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (! $this->end_date) {
            return null;
        }

        return now()->diffInDays($this->end_date, false);
    }

    /**
     * Sign the contract.
     */
    public function sign(string $signedBy, string $ipAddress, ?string $signatureData = null): void
    {
        $this->update([
            'status' => 'active',
            'signed_at' => now(),
            'signed_by' => $signedBy,
            'signature_ip' => $ipAddress,
            'signature_data' => $signatureData,
        ]);
    }

    /**
     * Scope for active contracts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for pending signature.
     */
    public function scopePendingSignature($query)
    {
        return $query->where('status', 'pending_signature');
    }

    /**
     * Scope for expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->active()
            ->whereNotNull('end_date')
            ->where('end_date', '<=', now()->addDays($days))
            ->where('end_date', '>', now());
    }

    /**
     * Generate a unique contract number.
     */
    public static function generateContractNumber(): string
    {
        $year = now()->format('Y');
        $lastContract = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastContract ? (int) substr($lastContract->contract_number, -4) + 1 : 1;

        return sprintf('CTR-%s-%04d', $year, $sequence);
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Contract $contract) {
            if (! $contract->contract_number) {
                $contract->contract_number = static::generateContractNumber();
            }
        });
    }
}
