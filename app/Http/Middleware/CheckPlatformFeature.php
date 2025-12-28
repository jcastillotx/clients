<?php

namespace App\Http\Middleware;

use App\Services\PlatformFeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlatformFeature
{
    public function __construct(
        protected PlatformFeatureService $platformFeatures
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$features): Response
    {
        // If no features specified, allow through
        if (empty($features)) {
            return $next($request);
        }

        // Check if ALL specified features are enabled
        foreach ($features as $feature) {
            if (! $this->platformFeatures->isEnabled($feature)) {
                // Feature is disabled - return 404 or redirect
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Feature not available',
                        'message' => 'This feature has been disabled by the administrator.',
                    ], 404);
                }

                // Redirect to dashboard with message
                return redirect()
                    ->route('dashboard')
                    ->with('error', 'This feature has been disabled by the administrator.');
            }
        }

        return $next($request);
    }
}
