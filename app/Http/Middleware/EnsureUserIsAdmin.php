<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        // Redirect clients away from admin routes (better UX than a hard 403)
        if ($user->isClient()) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'You do not have access to the admin area.');
        }

        if (!($user->isAdmin() || $user->isStaff())) {
            abort(403);
        }

        return $next($request);
    }
}

