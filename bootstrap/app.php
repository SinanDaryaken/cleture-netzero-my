<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureCurrentAuthenticationVersion;
use App\Http\Middleware\InitializeAvailableTenant;
use App\Http\Middleware\SetApplicationLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Middleware as InertiaMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            AddSecurityHeaders::class,
            SetApplicationLocale::class,
            InertiaMiddleware::class,
        ]);

        $middleware->alias([
            'auth.version' => EnsureCurrentAuthenticationVersion::class,
            'tenant.available' => InitializeAvailableTenant::class,
        ]);

        $middleware->redirectGuestsTo(fn (): string => route('login.create'));
        $middleware->redirectUsersTo(fn (): string => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
