<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class AnalyticsAccount extends Model
{
    protected $fillable = [
        'client_id',
        'platform',
        'property_id',
        'property_name',
        'account_name',
        'account_email',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_connected',
        'connected_at',
        'last_token_refresh',
        'last_sync_at',
        'scopes',
        'meta',
    ];

    protected $casts = [
        'is_connected' => 'boolean',
        'token_expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'last_token_refresh' => 'datetime',
        'last_sync_at' => 'datetime',
        'scopes' => 'array',
        'meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // Access token encryption
    public function setAccessTokenAttribute(?string $value): void
    {
        $this->attributes['access_token'] = $value !== null && $value !== '' ? Crypt::encryptString($value) : null;
    }

    public function getAccessTokenAttribute(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }

    // Refresh token encryption
    public function setRefreshTokenAttribute(?string $value): void
    {
        $this->attributes['refresh_token'] = $value !== null && $value !== '' ? Crypt::encryptString($value) : null;
    }

    public function getRefreshTokenAttribute(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Check if the access token is expired or about to expire
     */
    public function isTokenExpired(): bool
    {
        if (! $this->token_expires_at) {
            return false;
        }

        // Consider token expired if it expires within the next 5 minutes
        return Carbon::now()->addMinutes(5)->greaterThan($this->token_expires_at);
    }

    /**
     * Check if the account needs token refresh
     */
    public function needsTokenRefresh(): bool
    {
        return $this->is_connected && $this->isTokenExpired() && $this->refresh_token;
    }

    /**
     * Mark account as connected with OAuth data
     */
    public function markAsConnected(array $oauthData): void
    {
        $this->update([
            'access_token' => $oauthData['access_token'],
            'refresh_token' => $oauthData['refresh_token'] ?? null,
            'token_expires_at' => $oauthData['expires_at'] ?? null,
            'is_connected' => true,
            'connected_at' => now(),
            'scopes' => $oauthData['scopes'] ?? null,
            'meta' => array_merge($this->meta ?? [], [
                'last_connection_ip' => request()->ip(),
                'last_connection_user_agent' => request()->userAgent(),
            ]),
        ]);
    }

    /**
     * Mark account as disconnected
     */
    public function disconnect(): void
    {
        $this->update([
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
            'is_connected' => false,
        ]);
    }

    /**
     * Update tokens after refresh
     */
    public function updateTokens(string $accessToken, ?string $refreshToken = null, ?Carbon $expiresAt = null): void
    {
        $data = [
            'access_token' => $accessToken,
            'last_token_refresh' => now(),
        ];

        if ($refreshToken) {
            $data['refresh_token'] = $refreshToken;
        }

        if ($expiresAt) {
            $data['token_expires_at'] = $expiresAt;
        }

        $this->update($data);
    }

    /**
     * Get platform icon for UI
     */
    public function getPlatformIconAttribute(): string
    {
        return match ($this->platform) {
            'google_analytics' => 'fab fa-google',
            'google_analytics_4' => 'fab fa-google',
            'adobe_analytics' => 'fas fa-chart-line',
            'matomo' => 'fas fa-chart-area',
            default => 'fas fa-chart-bar',
        };
    }

    /**
     * Get platform color for UI
     */
    public function getPlatformColorAttribute(): string
    {
        return match ($this->platform) {
            'google_analytics', 'google_analytics_4' => '#F9AB00',
            'adobe_analytics' => '#FF0000',
            'matomo' => '#3152A0',
            default => '#6c757d',
        };
    }

    /**
     * Get platform display name
     */
    public function getPlatformDisplayNameAttribute(): string
    {
        return match ($this->platform) {
            'google_analytics' => 'Google Analytics (UA)',
            'google_analytics_4' => 'Google Analytics 4',
            'adobe_analytics' => 'Adobe Analytics',
            'matomo' => 'Matomo',
            default => ucfirst(str_replace('_', ' ', $this->platform)),
        };
    }

    /**
     * Get connection status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        if (! $this->is_connected) {
            return 'badge-secondary';
        }

        if ($this->isTokenExpired()) {
            return 'badge-warning';
        }

        return 'badge-success';
    }

    /**
     * Get connection status text
     */
    public function getStatusTextAttribute(): string
    {
        if (! $this->is_connected) {
            return 'Not Connected';
        }

        if ($this->isTokenExpired()) {
            return 'Token Expired';
        }

        return 'Connected';
    }
}
