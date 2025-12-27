<?php

namespace App\Services\Social;

use App\Models\SocialAccount;

interface OAuthServiceInterface
{
    /**
     * Get the OAuth authorization URL
     */
    public function getAuthorizationUrl(int $clientId): string;

    /**
     * Handle the OAuth callback and exchange code for tokens
     */
    public function handleCallback(string $code, int $clientId): SocialAccount;

    /**
     * Refresh an expired access token
     */
    public function refreshToken(SocialAccount $account): bool;

    /**
     * Disconnect/revoke the OAuth connection
     */
    public function disconnect(SocialAccount $account): bool;

    /**
     * Get user profile information from the platform
     */
    public function getUserProfile(SocialAccount $account): array;
}
