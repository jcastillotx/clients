<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Log page views for authenticated users
        if ($request->user() && $request->isMethod('GET') && !$request->ajax()) {
            // You can add activity logging here if needed
            // ActivityLog::log('Viewed: ' . $request->path());
        }

        return $response;
    }
}
