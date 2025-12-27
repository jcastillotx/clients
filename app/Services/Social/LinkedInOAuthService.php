<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LinkedInOAuthService implements OAuthServiceInterface
{
    protected string $clientId;

    protected string $clientSecret;

    protected string $redirectUri;

    public function __construct()
    {
        $this->clientId = config('services.linkedin.client_id');
        $this->clientSecret = config('services.linkedin.client_secret');
        $this->redirectUri = route('oauth.linkedin.callback');
    }

    /**
     * Get the OAuth authorization URL
     */
    public function getAuthorizationUrl(int $clientId): string
    {
        $state = base64_encode(json_encode([
            'client_id' => $clientId,
            'timestamp' => time(),
        ]));

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
            'scope' => implode(' ', [
                'openid',
                'profile',
                'email',
                'w_member_social',
            ]),
        ]);

        return "https://www.linkedin.com/oauth/v2/authorization?{$params}";
    }

    /**
     * Handle the OAuth callback and exchange code for tokens
     */
    public function handleCallback(string $code, int $clientId): SocialAccount
    {
        // Exchange code for access token
        $tokenResponse = Http::asForm()->post('https://www.linkedin.com/oauth/v2/accessToken', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
        ]);

        if ($tokenResponse->failed()) {
            throw new \Exception('Failed to exchange code for access token: '.$tokenResponse->body());
        }

        $tokenData = $tokenResponse->json();
        $accessToken = $tokenData['access_token'];
        $expiresIn = $tokenData['expires_in'] ?? 3600;

        // Get user profile
        $profileResponse = Http::withToken($accessToken)
            ->get('https://api.linkedin.com/v2/userinfo');

        if ($profileResponse->failed()) {
            throw new \Exception('Failed to fetch user profile: '.$profileResponse->body());
        }

        $profile = $profileResponse->json();

        // Create or update social account
        $account = SocialAccount::updateOrCreate(
            [
                'client_id' => $clientId,
                'platform' => 'linkedin',
                'account_id' => $profile['sub'],
            ],
            [
                'account_name' => $profile['name'] ?? null,
                'account_email' => $profile['email'] ?? null,
                'profile_picture_url' => $profile['picture'] ?? null,
            ]
        );

        // Mark as connected with OAuth data
        $account->markAsConnected([
            'access_token' => $accessToken,
            'refresh_token' => $tokenData['refresh_token'] ?? null,
            'expires_at' => Carbon::now()->addSeconds($expiresIn),
            'scopes' => explode(' ', $tokenData['scope'] ?? ''),
        ]);

        Log::info('LinkedIn OAuth connection established', [
            'client_id' => $clientId,
            'account_id' => $profile['sub'],
            'account_name' => $profile['name'] ?? 'Unknown',
        ]);

        return $account;
    }

    /**
     * Refresh an expired access token
     */
    public function refreshToken(SocialAccount $account): bool
    {
        if (! $account->refresh_token) {
            Log::warning('LinkedIn refresh token not available', [
                'account_id' => $account->id,
            ]);

            return false;
        }

        try {
            $response = Http::asForm()->post('https://www.linkedin.com/oauth/v2/accessToken', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $account->refresh_token,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($response->failed()) {
                Log::error('LinkedIn token refresh failed', [
                    'account_id' => $account->id,
                    'response' => $response->body(),
                ]);

                return false;
            }

            $data = $response->json();
            $expiresIn = $data['expires_in'] ?? 3600;

            $account->updateTokens(
                $data['access_token'],
                $data['refresh_token'] ?? $account->refresh_token,
                Carbon::now()->addSeconds($expiresIn)
            );

            Log::info('LinkedIn token refreshed successfully', [
                'account_id' => $account->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('LinkedIn token refresh exception', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Disconnect/revoke the OAuth connection
     */
    public function disconnect(SocialAccount $account): bool
    {
        if ($account->access_token) {
            try {
                // LinkedIn doesn't have a token revocation endpoint in OAuth 2.0
                // Tokens automatically expire after their validity period
                Log::info('LinkedIn OAuth disconnected (local only)', [
                    'account_id' => $account->id,
                ]);
            } catch (\Exception $e) {
                Log::warning('LinkedIn disconnect warning', [
                    'account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $account->disconnect();

        return true;
    }

    /**
     * Get user profile information from the platform
     */
    public function getUserProfile(SocialAccount $account): array
    {
        $response = Http::withToken($account->access_token)
            ->get('https://api.linkedin.com/v2/userinfo');

        if ($response->failed()) {
            throw new \Exception('Failed to fetch user profile: '.$response->body());
        }

        return $response->json();
    }
}
