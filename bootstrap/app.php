<?php

use App\Http\Middleware\AuthLogger;
use App\Http\Middleware\AuthRateLimiter;
use App\Http\Middleware\VerifyShopifyWebhook;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'shopify.webhook' => VerifyShopifyWebhook::class,
            'auth.logger' => AuthLogger::class,
            'auth.ratelimit' => AuthRateLimiter::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // Apply auth logger middleware to auth routes
        $middleware->group('auth-routes', [
            AuthLogger::class
        ]);
        $middleware->trustProxies('*');
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
