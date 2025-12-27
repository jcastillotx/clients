<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    public function __construct()
    {
        // Trust all proxies in production if behind Cloudflare/load balancer
        // Or set specific proxy IPs via TRUSTED_PROXIES env variable
        $trustedProxies = env('TRUSTED_PROXIES');

        if ($trustedProxies === '*') {
            // Trust all proxies (use with caution - only if behind known CDN/LB)
            $this->proxies = '*';
        } elseif ($trustedProxies) {
            // Trust specific proxy IPs (comma-separated)
            $this->proxies = array_map('trim', explode(',', $trustedProxies));
        } else {
            // Default: don't trust any proxies (most secure)
            $this->proxies = null;
        }
    }
}
