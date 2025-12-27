<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PinterestOAuthService implements OAuthServiceInterface
{
    protected string $authUrl = 'https://www.pinterest.com/oauth/';
    protected string $tokenUrl = 'https://api.pinterest.com/v5/oauth/token';
    protected string $apiUrl = 'https://api.pinterest.com/v5';

    public function getAuthorizationUrl(int $clientId): string
    {
        $state = Str::random(40);

        session([
            'pinterest_oauth_state' => $state,
            'pinterest_oauth_client_id' => $clientId,
        ]);

        $params = http_build_query([
            'client_id' => config('services.pinterest.client_id'),
            'redirect_uri' => route('oauth.pinterest.callback'),
            'response_type' => 'code',
            'scope' => 'boards:read,boards:write,pins:read,pins:write,user_accounts:read',
            'state' => $state,
        ]);

        return "{$this->authUrl}?{$params}";
    }

    public function handleCallback(string $code, int $clientId): SocialAccount
    {
        // Exchange code for tokens
        $response = Http::asForm()
            ->withBasicAuth(
                config('services.pinterest.client_id'),
                config('services.pinterest.client_secret')
            )
            ->post($this->tokenUrl, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => route('oauth.pinterest.callback'),
            ]);

        if ($response->failed()) {
            Log::error('Pinterest token exchange failed', ['response' => $response->body()]);
            throw new \Exception('Failed to authenticate with Pinterest');
        }

        $tokens = $response->json();

        // Get user info
        $userResponse = Http::withToken($tokens['access_token'])
            ->get("{$this->apiUrl}/user_account");

        if ($userResponse->failed()) {
            throw new \Exception('Failed to fetch Pinterest user info');
        }

        $user = $userResponse->json();

        session()->forget(['pinterest_oauth_state', 'pinterest_oauth_client_id']);

        return SocialAccount::updateOrCreate(
            [
                'client_id' => $clientId,
                'platform' => 'pinterest',
            ],
            [
                'account_name' => $user['username'] ?? 'Pinterest User',
                'account_id' => $user['id'] ?? null,
                'account_username' => $user['username'] ?? null,
                'profile_picture_url' => $user['profile_image'] ?? null,
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
            $response = Http::asForm()
                ->withBasicAuth(
                    config('services.pinterest.client_id'),
                    config('services.pinterest.client_secret')
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
            Log::error('Pinterest token refresh failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function disconnect(SocialAccount $account): bool
    {
        $account->update([
            'is_connected' => false,
            'access_token' => null,
            'refresh_token' => null,
        ]);

        return true;
    }

    public function getUserProfile(SocialAccount $account): array
    {
        $response = Http::withToken($account->access_token)
            ->get("{$this->apiUrl}/user_account");

        if ($response->failed()) {
            throw new \Exception('Failed to fetch Pinterest profile');
        }

        return $response->json();
    }

    /**
     * Get user's boards
     */
    public function getBoards(SocialAccount $account): array
    {
        $response = Http::withToken($account->access_token)
            ->get("{$this->apiUrl}/boards");

        if ($response->failed()) {
            return [];
        }

        return $response->json('items') ?? [];
    }

    /**
     * Create a pin
     */
    public function createPin(SocialAccount $account, string $boardId, string $title, string $description, string $imageUrl, ?string $link = null): array
    {
        $payload = [
            'board_id' => $boardId,
            'title' => $title,
            'description' => $description,
            'media_source' => [
                'source_type' => 'image_url',
                'url' => $imageUrl,
            ],
        ];

        if ($link) {
            $payload['link'] = $link;
        }

        $response = Http::withToken($account->access_token)
            ->post("{$this->apiUrl}/pins", $payload);

        if ($response->failed()) {
            $error = $response->json('message') ?? $response->body();
            throw new \Exception("Failed to create Pinterest pin: {$error}");
        }

        $pin = $response->json();

        return [
            'success' => true,
            'post_id' => $pin['id'],
            'url' => "https://www.pinterest.com/pin/{$pin['id']}/",
        ];
    }
}
