<?php

namespace App\Services\Social;

use App\Models\Setting;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Threads OAuth Service
 *
 * Uses the Threads API (built on Instagram Graph API).
 * Requires Meta App with Threads API access.
 */
class ThreadsOAuthService implements OAuthServiceInterface
{
    protected function getClientId(): ?string
    {
        return Setting::getValue('api.social.threads.client_id')
            ?: config('services.threads.client_id')
            ?: Setting::getValue('api.social.instagram.client_id')
            ?: config('services.instagram.client_id');
    }

    protected function getClientSecret(): ?string
    {
        return Setting::getValue('api.social.threads.client_secret')
            ?: config('services.threads.client_secret')
            ?: Setting::getValue('api.social.instagram.client_secret')
            ?: config('services.instagram.client_secret');
    }

    public function getAuthorizationUrl(string $state, ?int $clientId = null): string
    {
        $params = http_build_query([
            'client_id' => $this->getClientId(),
            'redirect_uri' => route('oauth.threads.callback'),
            'scope' => 'threads_basic,threads_content_publish,threads_read_replies,threads_manage_insights',
            'response_type' => 'code',
            'state' => $state,
        ]);

        return "https://threads.net/oauth/authorize?{$params}";
    }

    public function handleCallback(string $code): ?SocialAccount
    {
        $clientId = $this->getClientId();
        $clientSecret = $this->getClientSecret();

        // Exchange code for access token
        $response = Http::asForm()->post('https://graph.threads.net/oauth/access_token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => route('oauth.threads.callback'),
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        if (! $response->successful()) {
            Log::error('Threads OAuth token exchange failed', ['response' => $response->body()]);
            return null;
        }

        $tokens = $response->json();
        $accessToken = $tokens['access_token'] ?? null;
        $userId = $tokens['user_id'] ?? null;

        if (! $accessToken) {
            return null;
        }

        // Get long-lived token
        $longLivedResponse = Http::get('https://graph.threads.net/access_token', [
            'grant_type' => 'th_exchange_token',
            'client_secret' => $clientSecret,
            'access_token' => $accessToken,
        ]);

        if ($longLivedResponse->successful()) {
            $longLivedData = $longLivedResponse->json();
            $accessToken = $longLivedData['access_token'] ?? $accessToken;
            $expiresIn = $longLivedData['expires_in'] ?? 5184000; // 60 days default
        } else {
            $expiresIn = 3600;
        }

        // Get user profile
        $profileResponse = Http::get('https://graph.threads.net/v1.0/me', [
            'access_token' => $accessToken,
            'fields' => 'id,username,name,threads_profile_picture_url,threads_biography',
        ]);

        $profile = $profileResponse->successful() ? $profileResponse->json() : [];

        return SocialAccount::updateOrCreate(
            [
                'client_id' => session('oauth_client_id'),
                'platform' => 'threads',
            ],
            [
                'platform_user_id' => $userId ?? $profile['id'] ?? null,
                'platform_username' => $profile['username'] ?? null,
                'access_token' => $accessToken,
                'token_expires_at' => now()->addSeconds($expiresIn),
                'is_connected' => true,
                'meta' => [
                    'name' => $profile['name'] ?? null,
                    'profile_picture' => $profile['threads_profile_picture_url'] ?? null,
                    'biography' => $profile['threads_biography'] ?? null,
                ],
            ]
        );
    }

    public function refreshToken(SocialAccount $account): bool
    {
        $response = Http::get('https://graph.threads.net/refresh_access_token', [
            'grant_type' => 'th_refresh_token',
            'access_token' => $account->access_token,
        ]);

        if ($response->successful() && $response->json('access_token')) {
            $account->update([
                'access_token' => $response->json('access_token'),
                'token_expires_at' => now()->addSeconds($response->json('expires_in', 5184000)),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Create a post on Threads
     */
    public function createPost(SocialAccount $account, string $text, ?array $mediaUrls = null): array
    {
        $accessToken = $account->access_token;
        $userId = $account->platform_user_id;

        // Step 1: Create media container
        $containerData = [
            'media_type' => 'TEXT',
            'text' => $text,
            'access_token' => $accessToken,
        ];

        // If we have an image
        if (! empty($mediaUrls) && isset($mediaUrls[0])) {
            $containerData['media_type'] = 'IMAGE';
            $containerData['image_url'] = $mediaUrls[0];
        }

        $containerResponse = Http::post("https://graph.threads.net/v1.0/{$userId}/threads", $containerData);

        if (! $containerResponse->successful()) {
            return [
                'success' => false,
                'error' => 'Failed to create container: ' . $containerResponse->body(),
            ];
        }

        $containerId = $containerResponse->json()['id'] ?? null;
        if (! $containerId) {
            return ['success' => false, 'error' => 'No container ID returned'];
        }

        // Step 2: Publish
        $publishResponse = Http::post("https://graph.threads.net/v1.0/{$userId}/threads_publish", [
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
            'url' => "https://www.threads.net/@{$account->platform_username}/post/{$postId}",
        ];
    }
}
