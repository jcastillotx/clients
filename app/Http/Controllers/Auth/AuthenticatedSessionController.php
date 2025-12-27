<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\LoginHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => __('auth.failed'),
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = Auth::user();

        // Check if user is active (supports both legacy is_active + new status)
        $status = $user->status ?? ($user->is_active ? 'active' : 'inactive');
        if (! $user->is_active || $status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => $status === 'suspended'
                    ? 'Your account has been suspended. Please contact support.'
                    : 'Your account is not active. Please contact support.',
            ])->onlyInput('email');
        }

        // Check if client user belongs to an active client
        if ($user->isClient() && $user->client && ! $user->client->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Your company account is not active. Please contact support.',
            ])->onlyInput('email');
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Store login history
        try {
            LoginHistory::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'logged_in_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // ignore
        }

        // Log the activity
        ActivityLog::log(
            'Logged in',
            $user,
            null,
            'login',
            'auth'
        );

        $defaultRedirect = ($user->isAdmin() || $user->isStaff())
            ? route('admin.dashboard', absolute: false)
            : route('dashboard', absolute: false);

        return redirect()->intended($defaultRedirect);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Log the activity before logout
        if ($user) {
            ActivityLog::log(
                'Logged out',
                $user,
                null,
                'logout',
                'auth'
            );
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
