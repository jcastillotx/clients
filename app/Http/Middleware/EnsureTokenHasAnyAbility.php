<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenHasAnyAbility
{
    /**
     * Allow request if token has ANY of the required abilities.
     *
     * Usage: token.any_ability:admin,write
     */
    public function handle(Request $request, Closure $next, ...$abilities): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (!$user || !$token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        foreach ($abilities as $ability) {
            if ($token->can($ability)) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Forbidden (insufficient token permissions).'], 403);
    }
}

