<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content Security Policy - restrict resource loading to prevent XSS
        $csp = $this->buildContentSecurityPolicy();
        $response->headers->set('Content-Security-Policy', $csp);

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * Build the Content-Security-Policy header value.
     */
    protected function buildContentSecurityPolicy(): string
    {
        $appUrl = config('app.url', 'https://localhost');
        $parsedUrl = parse_url($appUrl);
        $appHost = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? 'localhost');

        $directives = [
            // Default: only allow resources from same origin
            "default-src 'self'",

            // Scripts: self, inline (for Livewire/Alpine), and common CDNs
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com https://js.stripe.com",

            // Styles: self, inline (for dynamic styles), and Google Fonts
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",

            // Fonts: self and Google Fonts
            "font-src 'self' https://fonts.gstatic.com data:",

            // Images: self, data URIs, blob URIs (for canvas/uploads), and common image hosts
            "img-src 'self' data: blob: https: http:",

            // Connect: API calls to self, Stripe, Pusher, and configured services
            "connect-src 'self' https://api.stripe.com wss://*.pusher.com https://*.pusher.com",

            // Frames: self and Stripe (for payment iframes)
            "frame-src 'self' https://js.stripe.com https://hooks.stripe.com",

            // Form actions: only to same origin
            "form-action 'self'",

            // Base URI: restrict to self
            "base-uri 'self'",

            // Object/embed: none (no Flash, etc.)
            "object-src 'none'",

            // Upgrade insecure requests in production
            "upgrade-insecure-requests",
        ];

        return implode('; ', $directives);
    }
}
