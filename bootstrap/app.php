<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend([
            \App\Http\Middleware\TrustProxies::class,
        ]);

        $middleware->append([
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
        // Trust proxies for proper client IP detection behind load balancers/CDNs
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES') === '*'
                ? '*'
                : (env('TRUSTED_PROXIES')
                    ? array_map('trim', explode(',', env('TRUSTED_PROXIES')))
                    : null
                ),
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                     \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                     \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                     \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
                     \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\ResolveWhiteLabelClient::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'ensure.client' => \App\Http\Middleware\EnsureUserIsClient::class,
            'ensure.admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'token.any_ability' => \App\Http\Middleware\EnsureTokenHasAnyAbility::class,
            'admin.ip_allowlist' => \App\Http\Middleware\EnsureAdminIpAllowlisted::class,
            'admin.2fa' => \App\Http\Middleware\EnsureTwoFactorEnabled::class,
            'feature' => \App\Http\Middleware\RequiresFeature::class,
            'feature.any' => \App\Http\Middleware\RequiresAnyFeature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
