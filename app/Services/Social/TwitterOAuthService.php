<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TwitterOAuthService implements OAuthServiceInterface
{
    protected string $authUrl = 'https://twitter.com/i/oauth2/authorize';
    protected string $tokenUrl = 'https://api.twitter.com/2/oauth2/token';
    protected string $apiUrl = 'https://api.twitter.com/2';

    public function getAuthorizationUrl(int $clientId): string
    {
        $state = Str::random(40);
        $codeVerifier = Str::random(128);
        $codeChallenge = strtr(rtrim(base64_encode(hash('sha256', $codeVerifier, true)), '='), '+/', '-_');

        // Store state and code verifier in session
        session([
            'twitter_oauth_state' => $state,
            'twitter_oauth_code_verifier' => $codeVerifier,
            'twitter_oauth_client_id' => $clientId,
        ]);

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.twitter.client_id'),
            'redirect_uri' => route('oauth.twitter.callback'),
            'scope' => 'tweet.read tweet.write users.read offline.access',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return "{$this->authUrl}?{$params}";
    }

    public function handleCallback(string $code, int $clientId): SocialAccount
    {
        $codeVerifier = session('twitter_oauth_code_verifier');

        // Exchange code for tokens
        $response = Http::asForm()
            ->withBasicAuth(
                config('services.twitter.client_id'),
                config('services.twitter.client_secret')
            )
            ->post($this->tokenUrl, [
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => route('oauth.twitter.callback'),
                'code_verifier' => $codeVerifier,
            ]);

        if ($response->failed()) {
            Log::error('Twitter token exchange failed', ['response' => $response->body()]);
            throw new \Exception('Failed to authenticate with X/Twitter');
        }

        $tokens = $response->json();

        // Get user info
        $userResponse = Http::withToken($tokens['access_token'])
            ->get("{$this->apiUrl}/users/me", [
                'user.fields' => 'id,name,username,profile_image_url',
            ]);

        if ($userResponse->failed()) {
            throw new \Exception('Failed to fetch X/Twitter user info');
        }

        $user = $userResponse->json('data');

        // Clear session
        session()->forget(['twitter_oauth_state', 'twitter_oauth_code_verifier', 'twitter_oauth_client_id']);

        // Create or update social account
        return SocialAccount::updateOrCreate(
            [
                'client_id' => $clientId,
                'platform' => 'twitter',
            ],
            [
                'account_name' => $user['name'],
                'account_id' => $user['id'],
                'account_username' => $user['username'],
                'profile_picture_url' => $user['profile_image_url'] ?? null,
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'token_expires_at' => isset($tokens['expires_in'])
                    ? now()->addSeconds($tokens['expires_in'])
                    : null,
                'is_connected' => true,
                'connected_at' => now(),
                'scopes' => explode(' ', $tokens['scope'] ?? ''),
            ]
        );
    }

    public function refreshToken(SocialAccount $account): bool
    {
        if (!$account->refresh_token) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth(
                    config('services.twitter.client_id'),
                    config('services.twitter.client_secret')
                )
                ->post($this->tokenUrl, [
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
            Log::error('Twitter token refresh failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function disconnect(SocialAccount $account): bool
    {
        try {
            // Revoke token
            Http::asForm()
                ->withBasicAuth(
                    config('services.twitter.client_id'),
                    config('services.twitter.client_secret')
                )
                ->post('https://api.twitter.com/2/oauth2/revoke', [
                    'token' => $account->access_token,
                    'token_type_hint' => 'access_token',
                ]);

            $account->update([
                'is_connected' => false,
                'access_token' => null,
                'refresh_token' => null,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Twitter disconnect failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getUserProfile(SocialAccount $account): array
    {
        $response = Http::withToken($account->access_token)
            ->get("{$this->apiUrl}/users/me", [
                'user.fields' => 'id,name,username,profile_image_url,public_metrics',
            ]);

        if ($response->failed()) {
            throw new \Exception('Failed to fetch Twitter profile');
        }

        return $response->json('data');
    }

    /**
     * Create a tweet
     */
    public function createTweet(SocialAccount $account, string $text, ?array $mediaIds = null): array
    {
        $payload = ['text' => $text];

        if (!empty($mediaIds)) {
            $payload['media'] = ['media_ids' => $mediaIds];
        }

        $response = Http::withToken($account->access_token)
            ->post("{$this->apiUrl}/tweets", $payload);

        if ($response->failed()) {
            $error = $response->json('detail') ?? $response->body();
            throw new \Exception("Failed to post tweet: {$error}");
        }

        $data = $response->json('data');

        return [
            'success' => true,
            'post_id' => $data['id'],
            'url' => "https://twitter.com/{$account->account_username}/status/{$data['id']}",
        ];
    }

    /**
     * Upload media for tweets
     */
    public function uploadMedia(SocialAccount $account, string $mediaUrl): ?string
    {
        // Twitter v2 API requires v1.1 for media upload
        // This is a simplified version - production would need chunked upload
        try {
            $imageResponse = Http::get($mediaUrl);
            if ($imageResponse->failed()) {
                return null;
            }

            $base64 = base64_encode($imageResponse->body());

            $response = Http::withToken($account->access_token)
                ->asForm()
                ->post('https://upload.twitter.com/1.1/media/upload.json', [
                    'media_data' => $base64,
                ]);

            if ($response->failed()) {
                return null;
            }

            return $response->json('media_id_string');
        } catch (\Exception $e) {
            Log::warning('Twitter media upload failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
