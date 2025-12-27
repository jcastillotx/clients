<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminIpAllowlisted
{
    public function handle(Request $request, Closure $next): Response
    {
        $allow = (array) config('security.admin_ip_allowlist', []);
        if (empty($allow)) {
            return $next($request);
        }

        $ip = (string) $request->ip();
        foreach ($allow as $rule) {
            if ($this->ipMatches($ip, (string) $rule)) {
                return $next($request);
            }
        }

        abort(403, 'Admin access is restricted by IP.');
    }

    private function ipMatches(string $ip, string $rule): bool
    {
        $rule = trim($rule);
        if ($rule === '') return false;

        // exact IP match
        if (strpos($rule, '/') === false) {
            return $ip === $rule;
        }

        // CIDR match (IPv4 only, MVP)
        [$subnet, $bits] = array_pad(explode('/', $rule, 2), 2, null);
        $bits = (int) $bits;
        $ipLong = ip2long($ip);
        $subnetLong = ip2long((string) $subnet);
        if ($ipLong === false || $subnetLong === false || $bits < 0 || $bits > 32) {
            return false;
        }

        $mask = -1 << (32 - $bits);
        $mask = $mask & 0xFFFFFFFF;
        return (($ipLong & $mask) === ($subnetLong & $mask));
    }
}

