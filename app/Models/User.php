<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'client_id',
        'is_active',
        'status',
        'last_login_at',
        'two_factor_enabled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'status' => 'string',
        'last_login_at' => 'datetime',
        'two_factor_enabled' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'email_verified_at',
        'last_login_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the client that the user belongs to.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Accessor: get the related client (safe proxy to relationship).
     */
    public function getClientAttribute(): ?Client
    {
        if ($this->relationLoaded('client')) {
            return $this->getRelation('client');
        }

        return $this->client()->getResults();
    }

    /**
     * Get requests created by this user.
     */
    public function createdRequests(): HasMany
    {
        return $this->hasMany(Request::class, 'created_by');
    }

    /**
     * Get requests assigned to this user.
     */
    public function assignedRequests(): HasMany
    {
        return $this->hasMany(Request::class, 'assigned_to');
    }

    /**
     * Get comments made by this user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(RequestComment::class);
    }

    /**
     * Get documents uploaded by this user.
     */
    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    /**
     * Get activity logs for this user.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Login history entries.
     */
    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class)->orderByDesc('logged_in_at')->orderByDesc('id');
    }

    /**
     * Staff-to-client assignments (account managers).
     */
    public function assignedClients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_staff')
            ->withPivot(['relationship'])
            ->withTimestamps();
    }

    /**
     * Convenience: list of assigned client IDs.
     *
     * @return array<int, int>
     */
    public function assignedClientIds(): array
    {
        return $this->assignedClients()->pluck('clients.id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Check if user is a client user.
     */
    public function isClient(): bool
    {
        return $this->hasRole('client') || $this->client_id !== null;
    }

    /**
     * Check if user is an admin/staff.
     */
    public function isStaff(): bool
    {
        return $this->hasRole('staff') || ($this->client_id === null && !$this->hasRole('client'));
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Check if user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isSuspended(): bool
    {
        return ($this->status ?? 'active') === 'suspended';
    }

    public function isInactive(): bool
    {
        return ($this->status ?? 'active') === 'inactive';
    }

    public function isActiveAccount(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return ($this->status ?? 'active') === 'active';
    }

    /**
     * Get the user's initials.
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';
        
        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        
        return substr($initials, 0, 2);
    }
}
