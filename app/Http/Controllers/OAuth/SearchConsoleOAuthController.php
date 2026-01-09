<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SearchConsoleOAuthController extends Controller
{
    /**
     * Redirect to Google Search Console OAuth
     */
    public function redirect(Request $request)
    {
        $user = $request->user();
        $clientId = $user->client_id;

        if (!$clientId) {
            return redirect()->route('client.seo')
                ->with('error', 'Client account not found. Please contact support.');
        }

        // Build Google OAuth URL for Search Console API
        $googleClientId = config('services.google.client_id');
        $redirectUri = route('oauth.gsc.callback');

        if (empty($googleClientId)) {
            return redirect()->route('client.seo')
                ->with('error', 'Google API is not configured. Please contact support.');
        }

        $state = base64_encode(json_encode([
            'client_id' => $clientId,
            'user_id' => $user->id,
            'timestamp' => time(),
            'csrf' => csrf_token(),
        ]));

        // Store state in session for verification
        session(['gsc_oauth_state' => $state]);

        $scopes = [
            'https://www.googleapis.com/auth/webmasters.readonly',
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

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    }

    /**
     * Handle Google Search Console OAuth callback
     */
    public function callback(Request $request)
    {
        try {
            // Verify state parameter
            $state = $request->get('state');
            $storedState = session('gsc_oauth_state');

            if (!$state || $state !== $storedState) {
                throw new \Exception('Invalid state parameter. Please try again.');
            }

            // Clear stored state
            session()->forget('gsc_oauth_state');

            $stateData = json_decode(base64_decode($state), true);
            $clientId = $stateData['client_id'] ?? null;
            $userId = $stateData['user_id'] ?? null;

            if (!$clientId) {
                throw new \Exception('Invalid client ID. Please try again.');
            }

            // Check for OAuth error
            if ($request->has('error')) {
                $errorDesc = $request->get('error_description', 'Authorization was cancelled or denied.');
                throw new \Exception($errorDesc);
            }

            $code = $request->get('code');
            if (!$code) {
                throw new \Exception('Authorization code not received. Please try again.');
            }

            // Exchange code for access token
            $tokenData = $this->exchangeCodeForToken($code);

            if (!isset($tokenData['refresh_token'])) {
                throw new \Exception('Failed to obtain refresh token. Please revoke access and try again.');
            }

            // Get user info
            $userInfo = $this->getUserInfo($tokenData['access_token']);

            // Get available sites from Search Console
            $sites = $this->getSites($tokenData['access_token']);

            // Update client with GSC credentials
            $client = Client::findOrFail($clientId);
            $client->update([
                'gsc_refresh_token' => encrypt($tokenData['refresh_token']),
                'gsc_connected_at' => now(),
                'meta' => array_merge($client->meta ?? [], [
                    'gsc_email' => $userInfo['email'] ?? null,
                    'gsc_sites' => $sites,
                    'gsc_connected_by' => $userId,
                ]),
            ]);

            // Auto-select site if client website matches
            if ($client->website && !$client->gsc_site_url) {
                $matchingSite = $this->findMatchingSite($client->website, $sites);
                if ($matchingSite) {
                    $client->update(['gsc_site_url' => $matchingSite]);
                }
            }

            $siteCount = count($sites);
            $message = "Google Search Console connected successfully! Found {$siteCount} verified site(s).";

            if ($client->gsc_site_url) {
                $message .= " Automatically selected: {$client->gsc_site_url}";
            } elseif ($siteCount > 0) {
                $message .= " Please select your website from the available sites.";
            }

            return redirect()->route('client.seo')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('GSC OAuth callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('client.seo')
                ->with('error', 'Failed to connect Google Search Console: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect Search Console
     */
    public function disconnect(Request $request)
    {
        $user = $request->user();

        if (!$user->client_id) {
            return back()->with('error', 'Client account not found.');
        }

        $client = Client::find($user->client_id);

        if (!$client) {
            return back()->with('error', 'Client not found.');
        }

        // Revoke token if possible
        if ($client->gsc_refresh_token) {
            try {
                $refreshToken = decrypt($client->gsc_refresh_token);
                Http::asForm()->post('https://oauth2.googleapis.com/revoke', [
                    'token' => $refreshToken,
                ]);
            } catch (\Exception $e) {
                // Continue even if revoke fails
                Log::warning('Failed to revoke GSC token', ['error' => $e->getMessage()]);
            }
        }

        // Clear GSC data
        $meta = $client->meta ?? [];
        unset($meta['gsc_email'], $meta['gsc_sites'], $meta['gsc_connected_by']);

        $client->update([
            'gsc_refresh_token' => null,
            'gsc_site_url' => null,
            'gsc_connected_at' => null,
            'meta' => $meta,
        ]);

        return back()->with('success', 'Google Search Console disconnected successfully.');
    }

    /**
     * Update selected site
     */
    public function updateSite(Request $request)
    {
        $request->validate([
            'site_url' => 'required|string|url',
        ]);

        $user = $request->user();

        if (!$user->client_id) {
            return back()->with('error', 'Client account not found.');
        }

        $client = Client::find($user->client_id);

        if (!$client || !$client->gsc_refresh_token) {
            return back()->with('error', 'Google Search Console is not connected.');
        }

        // Verify the site is in the available sites list
        $availableSites = $client->meta['gsc_sites'] ?? [];
        $siteUrl = $request->input('site_url');

        if (!in_array($siteUrl, $availableSites)) {
            return back()->with('error', 'Selected site is not available in your Search Console account.');
        }

        $client->update(['gsc_site_url' => $siteUrl]);

        return back()->with('success', 'Website selected: ' . $siteUrl);
    }

    /**
     * Refresh available sites
     */
    public function refreshSites(Request $request)
    {
        $user = $request->user();

        if (!$user->client_id) {
            return back()->with('error', 'Client account not found.');
        }

        $client = Client::find($user->client_id);

        if (!$client || !$client->gsc_refresh_token) {
            return back()->with('error', 'Google Search Console is not connected.');
        }

        try {
            $accessToken = $this->getAccessToken($client);
            $sites = $this->getSites($accessToken);

            $meta = $client->meta ?? [];
            $meta['gsc_sites'] = $sites;
            $client->update(['meta' => $meta]);

            return back()->with('success', 'Found ' . count($sites) . ' verified site(s).');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to refresh sites: ' . $e->getMessage());
        }
    }

    /**
     * Exchange authorization code for access token
     */
    private function exchangeCodeForToken(string $code): array
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => route('oauth.gsc.callback'),
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to exchange code for token: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get access token from refresh token
     */
    private function getAccessToken(Client $client): string
    {
        $refreshToken = decrypt($client->gsc_refresh_token);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to refresh access token');
        }

        return $response->json('access_token');
    }

    /**
     * Get user info from Google
     */
    private function getUserInfo(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get('https://www.googleapis.com/oauth2/v2/userinfo');

        if (!$response->successful()) {
            throw new \Exception('Failed to get user info');
        }

        return $response->json();
    }

    /**
     * Get list of sites from Search Console
     */
    private function getSites(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get('https://www.googleapis.com/webmasters/v3/sites');

        if (!$response->successful()) {
            return [];
        }

        $siteEntries = $response->json('siteEntry', []);

        return array_map(function ($site) {
            return $site['siteUrl'];
        }, $siteEntries);
    }

    /**
     * Find a matching site URL from available sites
     */
    private function findMatchingSite(string $websiteUrl, array $sites): ?string
    {
        $websiteUrl = rtrim($websiteUrl, '/');
        $parsedWebsite = parse_url($websiteUrl);
        $websiteHost = $parsedWebsite['host'] ?? '';

        foreach ($sites as $siteUrl) {
            // Check for exact match
            if (rtrim($siteUrl, '/') === $websiteUrl) {
                return $siteUrl;
            }

            // Check for domain property match (sc-domain:example.com)
            if (str_starts_with($siteUrl, 'sc-domain:')) {
                $domain = substr($siteUrl, 10);
                if ($domain === $websiteHost || str_ends_with($websiteHost, '.' . $domain)) {
                    return $siteUrl;
                }
            }

            // Check for URL prefix match
            $parsedSite = parse_url($siteUrl);
            $siteHost = $parsedSite['host'] ?? '';
            if ($siteHost === $websiteHost) {
                return $siteUrl;
            }
        }

        return null;
    }
}
