<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresFeature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $feature  The feature key required
     * @param  string|null  $redirect  Optional redirect route if access denied
     */
    public function handle(Request $request, Closure $next, string $feature, ?string $redirect = null): Response
    {
        $user = $request->user();

        // No user or no client - deny access
        if (!$user || !$user->client) {
            return $this->denyAccess($request, $redirect, 'No client associated with user');
        }

        // Check if client has the required feature
        if (!$user->client->hasFeature($feature)) {
            return $this->denyAccess(
                $request,
                $redirect,
                "Your plan does not include access to this feature. Please upgrade to access {$feature}."
            );
        }

        return $next($request);
    }

    /**
     * Handle access denial
     *
     * @param  Request  $request
     * @param  string|null  $redirect
     * @param  string  $message
     * @return Response
     */
    protected function denyAccess(Request $request, ?string $redirect, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => $message,
            ], 403);
        }

        if ($redirect) {
            return redirect()->route($redirect)
                ->with('error', $message);
        }

        abort(403, $message);
    }
}
