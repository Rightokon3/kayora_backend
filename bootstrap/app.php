<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin.guard' => \App\Http\Middleware\EnsureAdmin::class,
    ]);
})
    ->withExceptions(function (Exceptions $exceptions): void {
        // This app has no web login route — it's API-only for both the
        // customer and driver apps. Without this, an unauthenticated
        // request to any /api/* route crashes with "Route [login] not
        // defined" instead of returning a clean 401 JSON response.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.'
                ], 401);
            }
        });
    })->create();