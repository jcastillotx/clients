<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookOAuthService implements OAuthServiceInterface
{
    protected string $clientId;

    protected string $clientSecret;

    protected string $redirectUri;

    protected string $graphApiVersion = 'v18.0';

    public function __construct()
    {
        $this->clientId = config('services.facebook.client_id');
        $this->clientSecret = config('services.facebook.client_secret');
        $this->redirectUri = route('oauth.facebook.callback');
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
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
            'scope' => implode(',', [
                'pages_manage_posts',
                'pages_read_engagement',
                'pages_show_list',
                'business_management',
            ]),
            'response_type' => 'code',
        ]);

        return "https://www.facebook.com/{$this->graphApiVersion}/dialog/oauth?{$params}";
    }

    /**
     * Handle the OAuth callback and exchange code for tokens
     */
    public function handleCallback(string $code, int $clientId): SocialAccount
    {
        // Exchange code for access token
        $tokenResponse = Http::get("https://graph.facebook.com/{$this->graphApiVersion}/oauth/access_token", [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ]);

        if ($tokenResponse->failed()) {
            throw new \Exception('Failed to exchange code for access token: '.$tokenResponse->body());
        }

        $tokenData = $tokenResponse->json();
        $accessToken = $tokenData['access_token'];

        // Get long-lived token (60 days)
        $longLivedTokenResponse = Http::get("https://graph.facebook.com/{$this->graphApiVersion}/oauth/access_token", [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'fb_exchange_token' => $accessToken,
        ]);

        if ($longLivedTokenResponse->successful()) {
            $longLivedData = $longLivedTokenResponse->json();
            $accessToken = $longLivedData['access_token'];
            $expiresIn = $longLivedData['expires_in'] ?? 5184000; // 60 days default
        } else {
            $expiresIn = $tokenData['expires_in'] ?? 3600;
        }

        // Get user profile
        $profileResponse = Http::get("https://graph.facebook.com/{$this->graphApiVersion}/me", [
            'access_token' => $accessToken,
            'fields' => 'id,name,email,picture',
        ]);

        if ($profileResponse->failed()) {
            throw new \Exception('Failed to fetch user profile: '.$profileResponse->body());
        }

        $profile = $profileResponse->json();

        // Create or update social account
        $account = SocialAccount::updateOrCreate(
            [
                'client_id' => $clientId,
                'platform' => 'facebook',
                'account_id' => $profile['id'],
            ],
            [
                'account_name' => $profile['name'],
                'account_email' => $profile['email'] ?? null,
                'profile_picture_url' => $profile['picture']['data']['url'] ?? null,
            ]
        );

        // Mark as connected with OAuth data
        $account->markAsConnected([
            'access_token' => $accessToken,
            'expires_at' => Carbon::now()->addSeconds($expiresIn),
            'scopes' => ['pages_manage_posts', 'pages_read_engagement', 'pages_show_list'],
        ]);

        Log::info('Facebook OAuth connection established', [
            'client_id' => $clientId,
            'account_id' => $profile['id'],
            'account_name' => $profile['name'],
        ]);

        return $account;
    }

    /**
     * Refresh an expired access token
     * Note: Facebook long-lived tokens last 60 days, should be refreshed before expiry
     */
    public function refreshToken(SocialAccount $account): bool
    {
        if (! $account->access_token) {
            return false;
        }

        try {
            $response = Http::get("https://graph.facebook.com/{$this->graphApiVersion}/oauth/access_token", [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'fb_exchange_token' => $account->access_token,
            ]);

            if ($response->failed()) {
                Log::error('Facebook token refresh failed', [
                    'account_id' => $account->id,
                    'response' => $response->body(),
                ]);

                return false;
            }

            $data = $response->json();
            $expiresIn = $data['expires_in'] ?? 5184000; // 60 days

            $account->updateTokens(
                $data['access_token'],
                null,
                Carbon::now()->addSeconds($expiresIn)
            );

            Log::info('Facebook token refreshed successfully', [
                'account_id' => $account->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Facebook token refresh exception', [
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
        if (! $account->access_token) {
            $account->disconnect();

            return true;
        }

        try {
            // Revoke the token on Facebook's side
            $response = Http::delete("https://graph.facebook.com/{$this->graphApiVersion}/me/permissions", [
                'access_token' => $account->access_token,
            ]);

            Log::info('Facebook OAuth disconnected', [
                'account_id' => $account->id,
                'revoke_success' => $response->successful(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Facebook token revocation failed (continuing with local disconnect)', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
        }

        $account->disconnect();

        return true;
    }

    /**
     * Get user profile information from the platform
     */
    public function getUserProfile(SocialAccount $account): array
    {
        $response = Http::get("https://graph.facebook.com/{$this->graphApiVersion}/me", [
            'access_token' => $account->access_token,
            'fields' => 'id,name,email,picture,accounts',
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to fetch user profile: '.$response->body());
        }

        return $response->json();
    }
}
