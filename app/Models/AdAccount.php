<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class AdAccount extends Model
{
    protected $fillable = [
        'client_id',
        'platform',
        'account_id',
        'account_name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_connected',
        'connected_at',
        'last_sync_at',
        'capabilities',
        'meta',
    ];

    protected $casts = [
        'is_connected' => 'boolean',
        'token_expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'capabilities' => 'array',
        'meta' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(AdCampaign::class);
    }

    // Token encryption
    public function setAccessTokenAttribute(?string $value): void
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getAccessTokenAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function setRefreshTokenAttribute(?string $value): void
    {
        $this->attributes['refresh_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getRefreshTokenAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }

        return now()->addMinutes(5)->greaterThan($this->token_expires_at);
    }

    public function needsTokenRefresh(): bool
    {
        return $this->is_connected && $this->isTokenExpired() && $this->refresh_token;
    }

    public function updateTokens(string $accessToken, ?string $refreshToken = null, ?\DateTime $expiresAt = null): void
    {
        $data = [
            'access_token' => $accessToken,
        ];

        if ($refreshToken) {
            $data['refresh_token'] = $refreshToken;
        }

        if ($expiresAt) {
            $data['token_expires_at'] = $expiresAt;
        }

        $this->update($data);
    }

    public function getPlatformDisplayNameAttribute(): string
    {
        return match ($this->platform) {
            'google_ads' => 'Google Ads',
            'facebook_ads' => 'Facebook Ads',
            'instagram_ads' => 'Instagram Ads',
            'linkedin_ads' => 'LinkedIn Ads',
            'twitter_ads' => 'Twitter Ads',
            'tiktok_ads' => 'TikTok Ads',
            'pinterest_ads' => 'Pinterest Ads',
            default => ucfirst(str_replace('_', ' ', $this->platform)),
        };
    }

    public function getPlatformColorAttribute(): string
    {
        return match ($this->platform) {
            'google_ads' => '#4285F4',
            'facebook_ads' => '#1877F2',
            'instagram_ads' => '#E4405F',
            'linkedin_ads' => '#0A66C2',
            'twitter_ads' => '#1DA1F2',
            'tiktok_ads' => '#000000',
            'pinterest_ads' => '#E60023',
            default => '#6B7280',
        };
    }
}
