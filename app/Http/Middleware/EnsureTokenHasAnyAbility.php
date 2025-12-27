<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenHasAnyAbility
{
    public function handle(Request $request, Closure $next, string ...$abilities): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $token = $user->currentAccessToken();
        if (! $token) {
            abort(401);
        }

        foreach ($abilities as $ability) {
            $ability = trim((string) $ability);
            if ($ability !== '' && $token->can($ability)) {
                return $next($request);
            }
        }

        abort(403);
    }
}
