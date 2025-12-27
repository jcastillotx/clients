<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.enforce_admin_2fa', true)) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        // Only enforce for staff/admin (not clients).
        $isPrivileged = method_exists($user, 'isAdmin') && method_exists($user, 'isStaff')
            ? ($user->isAdmin() || $user->isStaff())
            : ((int) ($user->client_id ?? 0) === 0);

        if (! $isPrivileged) {
            return $next($request);
        }

        $path = ltrim($request->path(), '/');
        if (str_starts_with($path, 'two-factor')) {
            return $next($request);
        }

        if (! $user->two_factor_confirmed_at && ! (bool) ($user->two_factor_enabled ?? false)) {
            return redirect()->route('two-factor.setup');
        }

        return $next($request);
    }
}
