<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\LogsActivityWithContext;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Client extends Model
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
        'company_name',
        'contact_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'website',
        'industry',
        'status',
        'tier',
        'stripe_customer_id',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'status' => 'string',
        'tier' => 'string',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('clients')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the primary user associated with this client.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Get all users associated with this client.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get service requests for this client.
     */
    public function requests(): HasMany
    {
        return $this->hasMany(Request::class);
    }

    /**
     * Get contracts for this client.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Get invoices for this client.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get payments for this client.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get documents for this client.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function storageConnections(): HasMany
    {
        return $this->hasMany(StorageConnection::class);
    }

    public function storageSetting(): HasOne
    {
        return $this->hasOne(ClientStorageSetting::class);
    }

    /**
     * Get activity logs for this client.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Staff users assigned to this client (account managers).
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_staff')
            ->withPivot(['relationship'])
            ->withTimestamps();
    }

    /**
     * Get the full address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->zip_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Check if client is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get open requests count.
     */
    public function getOpenRequestsCountAttribute(): int
    {
        return $this->requests()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();
    }

    /**
     * Get an HTML status badge for UI.
     */
    public function getStatusBadgeAttribute(): string
    {
        $label = ucfirst($this->status);
        $class = match ($this->status) {
            'active' => 'bg-success',
            'inactive' => 'bg-secondary',
            'pending' => 'bg-warning',
            'suspended' => 'bg-danger',
            default => 'bg-secondary',
        };

        return sprintf('<span class="badge %s">%s</span>', $class, $label);
    }

    /**
     * Get unpaid invoices total.
     */
    public function getUnpaidInvoicesTotalAttribute(): float
    {
        return $this->invoices()
            ->whereIn('status', ['sent', 'overdue'])
            ->sum('amount');
    }

    /**
     * Scope for active clients.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for premium clients.
     */
    public function scopePremium($query)
    {
        return $query->where('tier', 'premium');
    }

    /**
     * Scope for clients by tier.
     */
    public function scopeTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }
}
