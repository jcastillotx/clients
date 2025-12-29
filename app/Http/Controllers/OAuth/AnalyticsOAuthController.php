<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnalyticsOAuthController extends Controller
{
    /**
     * Redirect to Google Analytics OAuth
     */
    public function googleRedirect(Request $request)
    {
        $clientId = $request->user()->client_id;

        if (! $clientId) {
            return redirect()->route('dashboard')
                ->with('error', 'Client not found.');
        }

        // Build Google OAuth URL for Analytics API
        $googleClientId = config('services.google.client_id');
        $redirectUri = route('oauth.analytics.google.callback');

        $state = base64_encode(json_encode([
            'client_id' => $clientId,
            'timestamp' => time(),
        ]));

        $scopes = [
            'https://www.googleapis.com/auth/analytics.readonly',
            'https://www.googleapis.com/auth/userinfo.email',
        ];

        $params = http_build_query([
            'client_id' => $googleClientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?'.$params);
    }

    /**
     * Handle Google Analytics OAuth callback
     */
    public function googleCallback(Request $request)
    {
        try {
            // Verify state parameter
            $state = $request->get('state');
            if (! $state) {
                throw new \Exception('Invalid state parameter');
            }

            $stateData = json_decode(base64_decode($state), true);
            $clientId = $stateData['client_id'] ?? null;

            if (! $clientId) {
                throw new \Exception('Invalid client ID in state');
            }

            // Check for error
            if ($request->has('error')) {
                throw new \Exception($request->get('error_description', 'OAuth cancelled'));
            }

            $code = $request->get('code');
            if (! $code) {
                throw new \Exception('Authorization code not provided');
            }

            // Exchange code for access token
            $tokenData = $this->exchangeCodeForToken($code);

            // Get user info and GA properties
            $userInfo = $this->getUserInfo($tokenData['access_token']);

            // Create or update analytics account
            $account = AnalyticsAccount::updateOrCreate(
                [
                    'client_id' => $clientId,
                    'platform' => 'google_analytics_4',
                    'account_email' => $userInfo['email'],
                ],
                [
                    'account_name' => $userInfo['name'] ?? $userInfo['email'],
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? null,
                    'token_expires_at' => isset($tokenData['expires_in'])
                        ? now()->addSeconds($tokenData['expires_in'])
                        : null,
                    'is_connected' => true,
                    'connected_at' => now(),
                    'scopes' => explode(' ', $tokenData['scope'] ?? ''),
                    'meta' => [
                        'user_info' => $userInfo,
                        'connection_ip' => $request->ip(),
                    ],
                ]
            );

            return redirect()->route('analytics.accounts')
                ->with('success', 'Google Analytics account connected successfully! You can now select which properties to track.');
        } catch (\Exception $e) {
            Log::error('Google Analytics OAuth callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('analytics.accounts')
                ->with('error', 'Failed to connect Google Analytics account: '.$e->getMessage());
        }
    }

    /**
     * Disconnect analytics account
     */
    public function disconnect(Request $request, string $platform)
    {
        $clientId = $request->user()->client_id;

        $account = AnalyticsAccount::where('client_id', $clientId)
            ->where('platform', $platform)
            ->first();

        if (! $account) {
            return back()->with('error', 'Analytics account not found.');
        }

        $account->disconnect();

        return back()->with('success', ucfirst(str_replace('_', ' ', $platform)).' analytics account disconnected successfully.');
    }

    /**
     * Exchange authorization code for access token
     */
    private function exchangeCodeForToken(string $code): array
    {
        $response = \Illuminate\Support\Facades\Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => route('oauth.analytics.google.callback'),
            'grant_type' => 'authorization_code',
        ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to exchange code for token: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Get user info from Google
     */
    private function getUserInfo(string $accessToken): array
    {
        $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
            ->get('https://www.googleapis.com/oauth2/v2/userinfo');

        if (! $response->successful()) {
            throw new \Exception('Failed to get user info');
        }

        return $response->json();
    }
}
