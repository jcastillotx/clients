<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TikTokOAuthService implements OAuthServiceInterface
{
    protected string $authUrl = 'https://www.tiktok.com/v2/auth/authorize/';
    protected string $tokenUrl = 'https://open.tiktokapis.com/v2/oauth/token/';
    protected string $apiUrl = 'https://open.tiktokapis.com/v2';

    public function getAuthorizationUrl(int $clientId): string
    {
        $state = Str::random(40);
        $codeVerifier = Str::random(64);
        $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');

        session([
            'tiktok_oauth_state' => $state,
            'tiktok_oauth_code_verifier' => $codeVerifier,
            'tiktok_oauth_client_id' => $clientId,
        ]);

        $params = http_build_query([
            'client_key' => config('services.tiktok.client_key'),
            'response_type' => 'code',
            'scope' => 'user.info.basic,video.publish,video.upload',
            'redirect_uri' => route('oauth.tiktok.callback'),
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return "{$this->authUrl}?{$params}";
    }

    public function handleCallback(string $code, int $clientId): SocialAccount
    {
        $codeVerifier = session('tiktok_oauth_code_verifier');

        $response = Http::asForm()->post($this->tokenUrl, [
            'client_key' => config('services.tiktok.client_key'),
            'client_secret' => config('services.tiktok.client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => route('oauth.tiktok.callback'),
            'code_verifier' => $codeVerifier,
        ]);

        if ($response->failed()) {
            Log::error('TikTok token exchange failed', ['response' => $response->body()]);
            throw new \Exception('Failed to authenticate with TikTok');
        }

        $tokens = $response->json();

        // Get user info
        $userResponse = Http::withToken($tokens['access_token'])
            ->get("{$this->apiUrl}/user/info/", [
                'fields' => 'open_id,union_id,avatar_url,display_name',
            ]);

        $user = $userResponse->json('data.user') ?? [];

        session()->forget(['tiktok_oauth_state', 'tiktok_oauth_code_verifier', 'tiktok_oauth_client_id']);

        return SocialAccount::updateOrCreate(
            [
                'client_id' => $clientId,
                'platform' => 'tiktok',
            ],
            [
                'account_name' => $user['display_name'] ?? 'TikTok User',
                'account_id' => $tokens['open_id'] ?? $user['open_id'] ?? null,
                'account_username' => $user['display_name'] ?? null,
                'profile_picture_url' => $user['avatar_url'] ?? null,
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'token_expires_at' => isset($tokens['expires_in'])
                    ? now()->addSeconds($tokens['expires_in'])
                    : null,
                'is_connected' => true,
                'connected_at' => now(),
                'scopes' => explode(',', $tokens['scope'] ?? ''),
            ]
        );
    }

    public function refreshToken(SocialAccount $account): bool
    {
        if (!$account->refresh_token) {
            return false;
        }

        try {
            $response = Http::asForm()->post($this->tokenUrl, [
                'client_key' => config('services.tiktok.client_key'),
                'client_secret' => config('services.tiktok.client_secret'),
                'grant_type' => 'refresh_token',
                'refresh_token' => $account->refresh_token,
            ]);

            if ($response->failed()) {
                return false;
            }

            $tokens = $response->json();

            $account->update([
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? $account->refresh_token,
                'token_expires_at' => isset($tokens['expires_in'])
                    ? now()->addSeconds($tokens['expires_in'])
                    : null,
                'last_token_refresh' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('TikTok token refresh failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function disconnect(SocialAccount $account): bool
    {
        try {
            Http::asForm()->post("{$this->apiUrl}/oauth/revoke/", [
                'client_key' => config('services.tiktok.client_key'),
                'client_secret' => config('services.tiktok.client_secret'),
                'token' => $account->access_token,
            ]);

            $account->update([
                'is_connected' => false,
                'access_token' => null,
                'refresh_token' => null,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('TikTok disconnect failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getUserProfile(SocialAccount $account): array
    {
        $response = Http::withToken($account->access_token)
            ->get("{$this->apiUrl}/user/info/", [
                'fields' => 'open_id,union_id,avatar_url,display_name,bio_description,profile_deep_link,is_verified,follower_count,following_count,likes_count,video_count',
            ]);

        if ($response->failed()) {
            throw new \Exception('Failed to fetch TikTok profile');
        }

        return $response->json('data.user') ?? [];
    }

    /**
     * Initialize video upload (TikTok requires video content)
     * Note: TikTok API primarily supports video content
     */
    public function initializeVideoUpload(SocialAccount $account, int $chunkSize, int $totalSize): ?string
    {
        $response = Http::withToken($account->access_token)
            ->post("{$this->apiUrl}/post/publish/inbox/video/init/", [
                'source_info' => [
                    'source' => 'FILE_UPLOAD',
                    'video_size' => $totalSize,
                    'chunk_size' => $chunkSize,
                    'total_chunk_count' => ceil($totalSize / $chunkSize),
                ],
            ]);

        if ($response->failed()) {
            return null;
        }

        return $response->json('data.publish_id');
    }
}
