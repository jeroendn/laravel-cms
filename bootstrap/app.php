<?php

use App\Http\Middleware\TrackActivity;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // TLS terminates at the shared Caddy, which proxies to Apache over
        // plain http. Trust its X-Forwarded-* headers so Laravel generates
        // https URLs (assets, routes) instead of mixed content. Trusting all
        // proxies is safe here: the container is only reachable through the
        // internal Docker network.
        $middleware->trustProxies(at: '*');

        // Last in the group: the session is started by then, so the user
        // resolves on every page, public site included.
        $middleware->appendToGroup('web', TrackActivity::class);

        $middleware->redirectUsersTo(fn(): string => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*'),
        );
    })->create();
