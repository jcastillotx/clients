<?php

namespace App\Services\Social;

use App\Models\Setting;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Instagram Business Account OAuth Service
 *
 * Uses Facebook Graph API for Instagram Business accounts.
 * Requires Facebook App with Instagram Basic Display or Instagram Graph API.
 */
class InstagramOAuthService implements OAuthServiceInterface
{
    protected function getClientId(): ?string
    {
        return Setting::getValue('api.social.instagram.client_id')
            ?: config('services.instagram.client_id')
            ?: Setting::getValue('api.social.facebook.client_id')
            ?: config('services.facebook.client_id');
    }

    protected function getClientSecret(): ?string
    {
        return Setting::getValue('api.social.instagram.client_secret')
            ?: config('services.instagram.client_secret')
            ?: Setting::getValue('api.social.facebook.client_secret')
            ?: config('services.facebook.client_secret');
    }

    public function getAuthorizationUrl(string $state, ?int $clientId = null): string
    {
        $params = http_build_query([
            'client_id' => $this->getClientId(),
            'redirect_uri' => route('oauth.instagram.callback'),
            'scope' => 'instagram_basic,instagram_content_publish,pages_show_list,pages_read_engagement',
            'response_type' => 'code',
            'state' => $state,
        ]);

        return "https://www.facebook.com/v18.0/dialog/oauth?{$params}";
    }

    public function handleCallback(string $code): ?SocialAccount
    {
        $clientId = $this->getClientId();
        $clientSecret = $this->getClientSecret();

        // Exchange code for access token
        $response = Http::asForm()->post('https://graph.facebook.com/v18.0/oauth/access_token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => route('oauth.instagram.callback'),
            'code' => $code,
        ]);

        if (! $response->successful()) {
            Log::error('Instagram OAuth token exchange failed', ['response' => $response->body()]);
            return null;
        }

        $tokens = $response->json();
        $accessToken = $tokens['access_token'] ?? null;

        if (! $accessToken) {
            return null;
        }

        // Get long-lived token
        $longLivedResponse = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'fb_exchange_token' => $accessToken,
        ]);

        if ($longLivedResponse->successful()) {
            $accessToken = $longLivedResponse->json()['access_token'] ?? $accessToken;
        }

        // Get Instagram Business Account ID
        $accountInfo = $this->getInstagramBusinessAccount($accessToken);
        if (! $accountInfo) {
            Log::error('Could not find Instagram Business account');
            return null;
        }

        return SocialAccount::updateOrCreate(
            [
                'client_id' => session('oauth_client_id'),
                'platform' => 'instagram',
            ],
            [
                'platform_user_id' => $accountInfo['id'],
                'platform_username' => $accountInfo['username'] ?? null,
                'access_token' => $accessToken,
                'token_expires_at' => now()->addDays(60),
                'is_connected' => true,
                'meta' => [
                    'name' => $accountInfo['name'] ?? null,
                    'followers_count' => $accountInfo['followers_count'] ?? null,
                    'facebook_page_id' => $accountInfo['page_id'] ?? null,
                ],
            ]
        );
    }

    protected function getInstagramBusinessAccount(string $accessToken): ?array
    {
        // Get Facebook pages
        $pagesResponse = Http::get('https://graph.facebook.com/v18.0/me/accounts', [
            'access_token' => $accessToken,
            'fields' => 'id,name,instagram_business_account',
        ]);

        if (! $pagesResponse->successful()) {
            return null;
        }

        $pages = $pagesResponse->json()['data'] ?? [];

        foreach ($pages as $page) {
            if (isset($page['instagram_business_account']['id'])) {
                // Get Instagram account details
                $igResponse = Http::get("https://graph.facebook.com/v18.0/{$page['instagram_business_account']['id']}", [
                    'access_token' => $accessToken,
                    'fields' => 'id,username,name,followers_count,media_count',
                ]);

                if ($igResponse->successful()) {
                    $igData = $igResponse->json();
                    $igData['page_id'] = $page['id'];
                    return $igData;
                }
            }
        }

        return null;
    }

    public function refreshToken(SocialAccount $account): bool
    {
        // Instagram/Facebook tokens are already long-lived (60 days)
        // They can be refreshed before expiry
        $response = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
            'fb_exchange_token' => $account->access_token,
        ]);

        if ($response->successful() && $response->json('access_token')) {
            $account->update([
                'access_token' => $response->json('access_token'),
                'token_expires_at' => now()->addDays(60),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Create a post on Instagram
     */
    public function createPost(SocialAccount $account, string $caption, ?string $imageUrl = null): array
    {
        if (! $imageUrl) {
            return ['success' => false, 'error' => 'Instagram requires an image URL'];
        }

        $accessToken = $account->access_token;
        $igUserId = $account->platform_user_id;

        // Step 1: Create media container
        $containerResponse = Http::post("https://graph.facebook.com/v18.0/{$igUserId}/media", [
            'image_url' => $imageUrl,
            'caption' => $caption,
            'access_token' => $accessToken,
        ]);

        if (! $containerResponse->successful()) {
            return [
                'success' => false,
                'error' => 'Failed to create media container: ' . $containerResponse->body(),
            ];
        }

        $containerId = $containerResponse->json()['id'] ?? null;
        if (! $containerId) {
            return ['success' => false, 'error' => 'No container ID returned'];
        }

        // Step 2: Publish the container
        $publishResponse = Http::post("https://graph.facebook.com/v18.0/{$igUserId}/media_publish", [
            'creation_id' => $containerId,
            'access_token' => $accessToken,
        ]);

        if (! $publishResponse->successful()) {
            return [
                'success' => false,
                'error' => 'Failed to publish: ' . $publishResponse->body(),
            ];
        }

        $postId = $publishResponse->json()['id'] ?? null;

        return [
            'success' => true,
            'post_id' => $postId,
            'url' => "https://www.instagram.com/p/{$postId}/",
        ];
    }
}
