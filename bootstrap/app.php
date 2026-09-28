<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Hostinger fronts every request through its own CDN (hcdn) — without this,
        // request()->ip() returns the CDN edge's IP for every visitor, not the real
        // client's, which would make the per-visitor rate limiters on /send and
        // /track (RateLimiter::hit('...:'.request()->ip())) shared across everyone
        // hitting that edge instead of scoped per person. The CDN's own IPs aren't
        // published/stable, so trusting all proxies is Laravel's documented answer
        // for exactly this case (any cloud load balancer with unknown IPs).
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
