<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Services\Social\BlueskyService;
use App\Services\Social\FacebookOAuthService;
use App\Services\Social\LinkedInOAuthService;
use App\Services\Social\PinterestOAuthService;
use App\Services\Social\TikTokOAuthService;
use App\Services\Social\TwitterOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SocialOAuthController extends Controller
{
    /**
     * Redirect to Facebook OAuth
     */
    public function facebookRedirect(Request $request)
    {
        $clientId = $request->user()->client_id;

        if (! $clientId) {
            return redirect()->route('dashboard')
                ->with('error', 'Client not found.');
        }

        $service = new FacebookOAuthService;

        return redirect($service->getAuthorizationUrl($clientId));
    }

    /**
     * Handle Facebook OAuth callback
     */
    public function facebookCallback(Request $request)
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

            $service = new FacebookOAuthService;
            $account = $service->handleCallback($code, $clientId);

            return redirect()->route('social.accounts')
                ->with('success', 'Facebook account connected successfully!');
        } catch (\Exception $e) {
            Log::error('Facebook OAuth callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('social.accounts')
                ->with('error', 'Failed to connect Facebook account: '.$e->getMessage());
        }
    }

    /**
     * Redirect to LinkedIn OAuth
     */
    public function linkedinRedirect(Request $request)
    {
        $clientId = $request->user()->client_id;

        if (! $clientId) {
            return redirect()->route('dashboard')
                ->with('error', 'Client not found.');
        }

        $service = new LinkedInOAuthService;

        return redirect($service->getAuthorizationUrl($clientId));
    }

    /**
     * Handle LinkedIn OAuth callback
     */
    public function linkedinCallback(Request $request)
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

            $service = new LinkedInOAuthService;
            $account = $service->handleCallback($code, $clientId);

            return redirect()->route('social.accounts')
                ->with('success', 'LinkedIn account connected successfully!');
        } catch (\Exception $e) {
            Log::error('LinkedIn OAuth callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('social.accounts')
                ->with('error', 'Failed to connect LinkedIn account: '.$e->getMessage());
        }
    }

    /**
     * Disconnect a social account
     */
    public function disconnect(Request $request, string $platform)
    {
        $clientId = $request->user()->client_id;

        $account = \App\Models\SocialAccount::where('client_id', $clientId)
            ->where('platform', $platform)
            ->first();

        if (! $account) {
            return redirect()->back()
                ->with('error', 'Account not found.');
        }

        try {
            $service = match ($platform) {
                'facebook' => new FacebookOAuthService,
                'linkedin' => new LinkedInOAuthService,
                'twitter' => new TwitterOAuthService,
                'pinterest' => new PinterestOAuthService,
                'tiktok' => new TikTokOAuthService,
                'bluesky' => new BlueskyService,
                default => null,
            };

            if ($service) {
                $service->disconnect($account);
            } else {
                $account->disconnect();
            }

            return redirect()->back()
                ->with('success', ucfirst($platform).' account disconnected successfully!');
        } catch (\Exception $e) {
            Log::error('Social account disconnect error', [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to disconnect account: '.$e->getMessage());
        }
    }

    /**
     * Redirect to Twitter/X OAuth
     */
    public function twitterRedirect(Request $request)
    {
        $clientId = $request->user()->client_id;

        if (! $clientId) {
            return redirect()->route('dashboard')
                ->with('error', 'Client not found.');
        }

        $service = new TwitterOAuthService;

        return redirect($service->getAuthorizationUrl($clientId));
    }

    /**
     * Handle Twitter/X OAuth callback
     */
    public function twitterCallback(Request $request)
    {
        try {
            $state = $request->get('state');
            $sessionState = session('twitter_oauth_state');

            if (! $state || $state !== $sessionState) {
                throw new \Exception('Invalid state parameter');
            }

            $clientId = session('twitter_oauth_client_id');

            if ($request->has('error')) {
                throw new \Exception($request->get('error_description', 'OAuth cancelled'));
            }

            $code = $request->get('code');
            if (! $code) {
                throw new \Exception('Authorization code not provided');
            }

            $service = new TwitterOAuthService;
            $account = $service->handleCallback($code, $clientId);

            return redirect()->route('social.accounts')
                ->with('success', 'X (Twitter) account connected successfully!');
        } catch (\Exception $e) {
            Log::error('Twitter OAuth callback error', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('social.accounts')
                ->with('error', 'Failed to connect X account: '.$e->getMessage());
        }
    }

    /**
     * Redirect to Pinterest OAuth
     */
    public function pinterestRedirect(Request $request)
    {
        $clientId = $request->user()->client_id;

        if (! $clientId) {
            return redirect()->route('dashboard')
                ->with('error', 'Client not found.');
        }

        $service = new PinterestOAuthService;

        return redirect($service->getAuthorizationUrl($clientId));
    }

    /**
     * Handle Pinterest OAuth callback
     */
    public function pinterestCallback(Request $request)
    {
        try {
            $state = $request->get('state');
            $sessionState = session('pinterest_oauth_state');

            if (! $state || $state !== $sessionState) {
                throw new \Exception('Invalid state parameter');
            }

            $clientId = session('pinterest_oauth_client_id');

            if ($request->has('error')) {
                throw new \Exception($request->get('error_description', 'OAuth cancelled'));
            }

            $code = $request->get('code');
            if (! $code) {
                throw new \Exception('Authorization code not provided');
            }

            $service = new PinterestOAuthService;
            $account = $service->handleCallback($code, $clientId);

            return redirect()->route('social.accounts')
                ->with('success', 'Pinterest account connected successfully!');
        } catch (\Exception $e) {
            Log::error('Pinterest OAuth callback error', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('social.accounts')
                ->with('error', 'Failed to connect Pinterest account: '.$e->getMessage());
        }
    }

    /**
     * Redirect to TikTok OAuth
     */
    public function tiktokRedirect(Request $request)
    {
        $clientId = $request->user()->client_id;

        if (! $clientId) {
            return redirect()->route('dashboard')
                ->with('error', 'Client not found.');
        }

        $service = new TikTokOAuthService;

        return redirect($service->getAuthorizationUrl($clientId));
    }

    /**
     * Handle TikTok OAuth callback
     */
    public function tiktokCallback(Request $request)
    {
        try {
            $state = $request->get('state');
            $sessionState = session('tiktok_oauth_state');

            if (! $state || $state !== $sessionState) {
                throw new \Exception('Invalid state parameter');
            }

            $clientId = session('tiktok_oauth_client_id');

            if ($request->has('error')) {
                throw new \Exception($request->get('error_description', 'OAuth cancelled'));
            }

            $code = $request->get('code');
            if (! $code) {
                throw new \Exception('Authorization code not provided');
            }

            $service = new TikTokOAuthService;
            $account = $service->handleCallback($code, $clientId);

            return redirect()->route('social.accounts')
                ->with('success', 'TikTok account connected successfully!');
        } catch (\Exception $e) {
            Log::error('TikTok OAuth callback error', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('social.accounts')
                ->with('error', 'Failed to connect TikTok account: '.$e->getMessage());
        }
    }

    /**
     * Connect Bluesky account (uses app password, not OAuth)
     */
    public function blueskyConnect(Request $request)
    {
        $request->validate([
            'handle' => 'required|string',
            'app_password' => 'required|string',
        ]);

        $clientId = $request->user()->client_id;

        if (! $clientId) {
            return redirect()->route('dashboard')
                ->with('error', 'Client not found.');
        }

        try {
            $service = new BlueskyService;
            $account = $service->connect($clientId, $request->handle, $request->app_password);

            return redirect()->route('social.accounts')
                ->with('success', 'Bluesky account connected successfully!');
        } catch (\Exception $e) {
            Log::error('Bluesky connect error', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('social.accounts')
                ->with('error', 'Failed to connect Bluesky account: '.$e->getMessage());
        }
    }
}
