<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresAnyFeature
{
    /**
     * Handle an incoming request.
     *
     * Client must have at least ONE of the specified features
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$features  Variable number of feature keys
     */
    public function handle(Request $request, Closure $next, string ...$features): Response
    {
        $user = $request->user();

        // No user or no client - deny access
        if (!$user || !$user->client) {
            return $this->denyAccess($request, 'No client associated with user');
        }

        // Check if client has ANY of the required features
        if (!$user->client->hasAnyFeature($features)) {
            $featureList = implode(' or ', $features);
            return $this->denyAccess(
                $request,
                "Your plan does not include access to this feature. You need one of: {$featureList}."
            );
        }

        return $next($request);
    }

    /**
     * Handle access denial
     *
     * @param  Request  $request
     * @param  string  $message
     * @return Response
     */
    protected function denyAccess(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => $message,
            ], 403);
        }

        abort(403, $message);
    }
}
