<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce TLS 1.3 Middleware
 *
 * Ensures all requests use HTTPS with TLS 1.2+ (preferring TLS 1.3).
 * Part of SOC2 Type II compliance for data in transit.
 */
class EnforceTls
{
    /**
     * Minimum acceptable TLS version.
     */
    protected const MIN_TLS_VERSION = '1.2';

    /**
     * Preferred TLS version.
     */
    protected const PREFERRED_TLS_VERSION = '1.3';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip in local development if configured
        if (app()->isLocal() && ! config('security.enforce_tls_local', false)) {
            return $next($request);
        }

        // Redirect HTTP to HTTPS
        if (! $request->secure() && config('security.force_https', true)) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        // Check TLS version from server variables (if available)
        $tlsVersion = $this->getTlsVersion($request);

        if ($tlsVersion && version_compare($tlsVersion, self::MIN_TLS_VERSION, '<')) {
            return response()->json([
                'error' => 'Insecure Connection',
                'message' => 'TLS 1.2 or higher is required. Please upgrade your browser or client.',
            ], 426); // Upgrade Required
        }

        $response = $next($request);

        // Add security headers for TLS
        $this->addSecurityHeaders($response);

        return $response;
    }

    /**
     * Get TLS version from request.
     */
    protected function getTlsVersion(Request $request): ?string
    {
        // Check various server variables that might contain TLS version
        $serverVars = [
            'SSL_PROTOCOL',
            'SERVER_PROTOCOL',
            'HTTPS_PROTOCOL',
        ];

        foreach ($serverVars as $var) {
            $value = $request->server($var);
            if ($value && preg_match('/TLSv?([\d.]+)/i', $value, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Add security headers to response.
     */
    protected function addSecurityHeaders(Response $response): void
    {
        // Strict Transport Security - enforce HTTPS for 1 year
        if (config('security.hsts_enabled', true)) {
            $maxAge = config('security.hsts_max_age', 31536000);
            $includeSubdomains = config('security.hsts_include_subdomains', true);
            $preload = config('security.hsts_preload', false);

            $hstsValue = "max-age={$maxAge}";
            if ($includeSubdomains) {
                $hstsValue .= '; includeSubDomains';
            }
            if ($preload) {
                $hstsValue .= '; preload';
            }

            $response->headers->set('Strict-Transport-Security', $hstsValue);
        }

        // Expect-CT - Certificate Transparency
        if (config('security.expect_ct_enabled', false)) {
            $response->headers->set('Expect-CT', 'max-age=86400, enforce');
        }

        // Upgrade insecure requests
        $response->headers->set('Content-Security-Policy',
            $response->headers->get('Content-Security-Policy', '') .
            (str_contains($response->headers->get('Content-Security-Policy', ''), 'upgrade-insecure-requests')
                ? ''
                : '; upgrade-insecure-requests')
        );
    }
}
