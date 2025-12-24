<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isClient()) {
            $client = $user->client;

            if (!$client || !$client->isActive()) {
                auth()->logout();
                
                return redirect()
                    ->route('login')
                    ->withErrors(['email' => 'Your account is not active. Please contact support.']);
            }
        }

        return $next($request);
    }
}
