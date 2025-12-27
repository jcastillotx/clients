<?php

namespace App\Http\Controllers\OAuth;

use App\Http\Controllers\Controller;
use App\Services\Social\FacebookOAuthService;
use App\Services\Social\LinkedInOAuthService;
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
}
